<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\QrScanEvent;
use App\Support\BusinessAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrAnalyticsController extends Controller
{
    private const PERIODS = [7, 30, 90];

    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $business);
        $period = $this->period($request);
        $from = now()->subDays($period - 1)->startOfDay();
        $visibleQrIds = $access->visibleQrCodes($request->user(), $business)->select('qr_codes.id');

        $codes = $access->visibleQrCodes($request->user(), $business)
            ->withCount(['scanEvents as recent_scans_count' => fn ($query) => $query->where('scanned_at', '>=', $from)])
            ->orderByDesc('recent_scans_count')->get();

        $daily = QrScanEvent::query()->whereIn('qr_code_id', clone $visibleQrIds)
            ->where('scanned_at', '>=', $from)
            ->selectRaw('DATE(scanned_at) as scan_date, COUNT(*) as scans')
            ->groupBy('scan_date')->orderBy('scan_date')->pluck('scans', 'scan_date');

        $days = collect(range(0, $period - 1))->map(function (int $offset) use ($daily, $period): array {
            $date = now()->subDays($period - 1 - $offset)->toDateString();
            return ['date' => $date, 'label' => Carbon::parse($date)->format('d.m'), 'scans' => (int) ($daily[$date] ?? 0)];
        });

        $devices = QrScanEvent::query()->whereIn('qr_code_id', clone $visibleQrIds)->where('scanned_at', '>=', $from)
            ->selectRaw("COALESCE(device_type, 'unknown') as device, COUNT(*) as scans")
            ->groupBy('device')->orderByDesc('scans')->get();

        $referrers = QrScanEvent::query()->whereIn('qr_code_id', clone $visibleQrIds)->where('scanned_at', '>=', $from)
            ->selectRaw("COALESCE(referrer_host, 'პირდაპირი / უცნობი') as source, COUNT(*) as scans")
            ->groupBy('source')->orderByDesc('scans')->limit(8)->get();

        $campaigns = QrScanEvent::query()->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->whereIn('qr_codes.id', clone $visibleQrIds)->where('qr_scan_events.scanned_at', '>=', $from)
            ->selectRaw("COALESCE(qr_codes.campaign_name, 'სტანდარტული') as label, COUNT(*) as scans")
            ->groupBy('qr_codes.campaign_name')->orderByDesc('scans')->get();

        $placements = QrScanEvent::query()->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->leftJoin('business_branches', 'qr_codes.branch_id', '=', 'business_branches.id')
            ->whereIn('qr_codes.id', clone $visibleQrIds)->where('qr_scan_events.scanned_at', '>=', $from)
            ->selectRaw("COALESCE(business_branches.name, 'ყველა ფილიალი') as branch_name, COALESCE(qr_codes.placement_label, qr_codes.name) as placement, COUNT(*) as scans")
            ->groupBy('business_branches.name', 'qr_codes.placement_label', 'qr_codes.name')
            ->orderByDesc('scans')->limit(20)->get();

        $recentTotal = (int) $days->sum('scans');
        $totalScans = (int) $access->visibleQrCodes($request->user(), $business)->sum('scan_count');

        return view('analytics.index', compact('business', 'codes', 'days', 'totalScans', 'recentTotal', 'devices', 'referrers', 'campaigns', 'placements', 'period'));
    }

    public function export(Request $request, Business $business, BusinessAccess $access): StreamedResponse
    {
        $access->assertCanViewAnalytics($request->user(), $business);
        $period = $this->period($request);
        $from = now()->subDays($period - 1)->startOfDay();
        $visibleQrIds = $access->visibleQrCodes($request->user(), $business)->select('qr_codes.id');

        $events = QrScanEvent::query()->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->leftJoin('business_branches', 'qr_codes.branch_id', '=', 'business_branches.id')
            ->whereIn('qr_codes.id', $visibleQrIds)->where('qr_scan_events.scanned_at', '>=', $from)
            ->orderBy('qr_scan_events.scanned_at')
            ->select([
                'qr_scan_events.scanned_at', 'qr_codes.name as qr_name', 'qr_codes.category',
                'qr_codes.campaign_name', 'qr_codes.placement_label', 'business_branches.name as branch_name',
                'qr_scan_events.device_type', 'qr_scan_events.referrer_host',
            ]);

        $filename = 'oneqr-analytics-' . $business->id . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($events): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['თარიღი', 'QR კოდი', 'კატეგორია', 'კამპანია', 'განთავსება', 'ფილიალი', 'მოწყობილობა', 'წყარო']);
            foreach ($events->cursor() as $event) {
                $values = [$event->scanned_at, $event->qr_name, $event->category, $event->campaign_name, $event->placement_label, $event->branch_name, $event->device_type, $event->referrer_host];
                fputcsv($output, array_map(fn ($value) => self::safeCsvValue($value), $values));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(Request $request): int
    {
        $period = (int) $request->query('days', 30);
        return in_array($period, self::PERIODS, true) ? $period : 30;
    }

    private static function safeCsvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
    }
}
