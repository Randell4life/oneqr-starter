<?php

namespace App\\Http\\Controllers;

use App\\Models\\BusinessSubscriptionPayment;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;
use Illuminate\\View\\View;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

class PlatformSubscriptionFinanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePlatformAdmin($request);
        [$from, $to, $status] = $this->filters($request);

        $base = BusinessSubscriptionPayment::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $summary = (clone $base)
            ->select('status', DB::raw('COUNT(*) as payments_count'), DB::raw('SUM(amount) as amount_total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $payments = (clone $base)
            ->with(['business:id,name,slug', 'plan:id,name,slug'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('platform.subscription-finance', [
            'payments' => $payments,
            'summary' => $summary,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'status' => $status,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePlatformAdmin($request);
        [$from, $to, $status] = $this->filters($request);

        $query = BusinessSubscriptionPayment::query()
            ->with(['business:id,name', 'plan:id,name'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->orderBy('created_at');

        $filename = 'oneqr-subscription-payments-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['თარიღი', 'ბიზნესი', 'გეგმა', 'თანხა', 'ვალუტა', 'სტატუსი', 'პროვაიდერი', 'Merchant ID', 'Provider ID']);

            foreach ($query->cursor() as $payment) {
                fputcsv($output, [
                    $payment->created_at?->format('Y-m-d H:i:s'),
                    $payment->business?->name,
                    $payment->plan?->name,
                    $payment->amount,
                    $payment->currency,
                    $payment->status,
                    $payment->provider,
                    $payment->merchant_payment_id,
                    $payment->provider_payment_id,
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'initiated', 'paid', 'failed', 'expired', 'review'])],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])
            : now()->subDays(29)->startOfDay();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])
            : now();

        if ($from->gt($to)) {
            throw \\Illuminate\\Validation\\ValidationException::withMessages([
                'from' => 'საწყისი თარიღი საბოლოო თარიღზე გვიან ვერ იქნება.',
            ]);
        }

        return [$from, $to, $validated['status'] ?? null];
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);
    }
}
