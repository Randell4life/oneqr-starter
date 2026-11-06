<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\QrCode;
use App\Support\BusinessAccess;
use App\Support\PlanAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessQrController extends Controller
{
    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewQr($request->user(), $business);

        $membership = $access->membership($request->user(), $business);
        $qrQuery = $access->visibleQrCodes($request->user(), $business)->with('branch');

        if ($request->filled('campaign')) {
            $qrQuery->where('campaign_name', $request->string('campaign')->toString());
        }
        if ($request->filled('placement')) {
            $qrQuery->where('placement_label', 'like', '%'.$request->string('placement')->toString().'%');
        }
        if ($request->filled('branch_id')) {
            $qrQuery->where('branch_id', $request->integer('branch_id'));
        }

        $branchesQuery = $business->branches()->where('is_active', true)->orderBy('name');
        if (! $access->isOwner($request->user(), $business) && $membership?->branch_id) {
            $branchesQuery->whereKey($membership->branch_id);
        }

        return view('qr.index', [
            'business' => $business,
            'qrCodes' => $qrQuery->latest()->paginate(12)->withQueryString(),
            'branches' => $branchesQuery->get(),
            'canManageQr' => $access->canManageQr($request->user(), $business),
            'membership' => $membership,
            'filters' => $request->only(['campaign', 'placement', 'branch_id']),
        ]);
    }

    public function store(Request $request, Business $business, BusinessAccess $access, PlanAccess $plans): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);

        $plans->assertCanCreate($business, 'qr_codes');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'destination_url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'category' => ['required', Rule::in(['general', 'menu', 'product', 'contact', 'booking', 'review', 'campaign', 'table'])],
            'placement_label' => ['nullable', 'string', 'max:100'],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'integer', Rule::exists('business_branches', 'id')->where('business_id', $business->id)],
        ]);

        $membership = $access->membership($request->user(), $business);
        $branchId = $membership ? $membership->branch_id : ($data['branch_id'] ?? null);

        $business->qrCodes()->create([
            'name' => $data['name'],
            'destination_url' => $data['destination_url'],
            'category' => $data['category'],
            'placement_label' => $data['placement_label'] ?? null,
            'campaign_name' => $data['campaign_name'] ?? null,
            'branch_id' => $branchId,
        ]);

        return redirect()->route('business.qr.index', $business)->with('status', 'QR კოდი წარმატებით შეიქმნა.');
    }

    public function update(Request $request, Business $business, int $qrCode, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $access->visibleQrCodes($request->user(), $business)->findOrFail($qrCode);
        $access->assertCanAccessQr($request->user(), $business, $record);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'destination_url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'category' => ['required', Rule::in(['general', 'menu', 'product', 'contact', 'booking', 'review', 'campaign'])],
            'is_active' => ['required', 'boolean'],
            'placement_label' => ['nullable', 'string', 'max:100'],
            'campaign_name' => ['nullable', 'string', 'max:120'],
        ]);

        $record->update($data);

        return back()->with('status', 'QR კოდი განახლდა.');
    }

    public function destroy(Request $request, Business $business, int $qrCode, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $access->visibleQrCodes($request->user(), $business)->findOrFail($qrCode);
        $access->assertCanAccessQr($request->user(), $business, $record);
        $record->delete();

        return back()->with('status', 'QR კოდი წაიშალა.');
    }
}
