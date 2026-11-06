<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\BusinessAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessInboxController extends Controller
{
    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewQr($request->user(), $business);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $allowedStatuses = ['new', 'contacted', 'processing', 'completed', 'cancelled'];

        $inquiries = $business->inquiries()->with('interactions.user')
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            }))
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10, ['*'], 'inquiries_page')
            ->withQueryString();

        $orderRequests = $business->orderRequests()
            ->with(['catalogItem', 'interactions.user'])
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            }))
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        return view('business.inbox', [
            'business' => $business,
            'inquiries' => $inquiries,
            'orderRequests' => $orderRequests,
            'canManage' => $access->canManageQr($request->user(), $business),
            'search' => $search,
            'status' => $status,
            'newCount' => $business->inquiries()->where('status', 'new')->count()
                + $business->orderRequests()->where('status', 'new')->count(),
            'followUpsDue' => $business->inquiries()->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now())->count()
                + $business->orderRequests()->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now())->count(),
        ]);
    }

    public function storeInteraction(Request $request, Business $business, string $type, int $record, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        abort_unless(in_array($type, ['inquiry', 'order-request'], true), 404);

        $relation = $type === 'inquiry' ? $business->inquiries() : $business->orderRequests();
        $item = $relation->findOrFail($record);

        $data = $request->validate([
            'channel' => ['required', Rule::in(['phone', 'email', 'whatsapp', 'in_person', 'note'])],
            'summary' => ['required', 'string', 'max:3000'],
            'occurred_at' => ['required', 'date'],
        ]);

        $item->interactions()->create($data + [
            'business_id' => $business->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'კომუნიკაციის ჩანაწერი ისტორიას დაემატა.');
    }

    public function update(Request $request, Business $business, string $type, int $record, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);

        abort_unless(in_array($type, ['inquiry', 'order-request'], true), 404);

        $relation = $type === 'inquiry' ? $business->inquiries() : $business->orderRequests();
        $item = $relation->findOrFail($record);
        $statuses = $type === 'inquiry'
            ? ['new', 'contacted', 'completed', 'cancelled']
            : ['new', 'processing', 'completed', 'cancelled'];

        $data = $request->validate([
            'status' => ['required', Rule::in($statuses)],
            'follow_up_at' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $item->update($data);

        return back()->with('status', 'მომხმარებლის ჩანაწერი განახლდა.');
    }
}
