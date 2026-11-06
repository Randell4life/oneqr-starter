<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\BusinessAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class QrAnalyticsController extends Controller
{
    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewAnalytics($request->user(), $business);
        $membership = $access->membership($request->user(), $business);
        $from = now()->subDays(29)->startOfDay();

        $codesQuery = $access->visibleQrCodes($request->user(), $business);
        $codes = $codesQuery->withCount([
            'scanEvents as recent_scans_count' => fn ($query) => $query->where('scanned_at', '>=', $from),
        ])->withMax('scanEvents', 'scanned_at')->orderByDesc('recent_scans_count')->get();

        $dailyQuery = $business->qrCodes()
            ->join('qr_scan_events', 'qr_codes.id', '=', 'qr_scan_events.qr_code_id')
            ->where('qr_scan_events.scanned_at', '>=', $from);

        if ($membership?->branch_id) {
            $dailyQuery->where(function ($query) use ($membership): void {
                $query->whereNull('qr_codes.branch_id')->orWhere('qr_codes.branch_id', $membership->branch_id);
            });
        }

        $daily = $dailyQuery->selectRaw('DATE(qr_scan_events.scanned_at) as scan_date, COUNT(*) as scans')
            ->groupBy('scan_date')->orderBy('scan_date')->pluck('scans', 'scan_date');

        $days = collect(range(0, 29))->map(function (int $offset) use ($daily): array {
            $date = now()->subDays(29 - $offset)->toDateString();

            return ['date' => $date, 'label' => Carbon::parse($date)->format('d.m'), 'scans' => (int) ($daily[$date] ?? 0)];
        });

        $visibleQrIds = $access->visibleQrCodes($request->user(), $business)->pluck('qr_codes.id');

        $devices = \App\Models\QrScanEvent::query()
            ->whereIn('qr_code_id', $visibleQrIds)
            ->where('scanned_at', '>=', $from)
            ->selectRaw("COALESCE(device_type, 'unknown') as device, COUNT(*) as scans")
            ->groupBy('device')
            ->orderByDesc('scans')
            ->get();

        $referrers = \App\Models\QrScanEvent::query()
            ->whereIn('qr_code_id', $visibleQrIds)
            ->where('scanned_at', '>=', $from)
            ->selectRaw("COALESCE(referrer_host, 'პირდაპირი / უცნობი') as source, COUNT(*) as scans")
            ->groupBy('source')
            ->orderByDesc('scans')
            ->limit(8)
            ->get();


        $campaigns = \App\Models\QrScanEvent::query()
            ->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->whereIn('qr_codes.id', $visibleQrIds)
            ->where('qr_scan_events.scanned_at', '>=', $from)
            ->selectRaw("COALESCE(qr_codes.campaign_name, 'სტანდარტული') as label, COUNT(*) as scans")
            ->groupBy('qr_codes.campaign_name')
            ->orderByDesc('scans')
            ->get();

        $placements = \App\Models\QrScanEvent::query()
            ->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->leftJoin('business_branches', 'qr_codes.branch_id', '=', 'business_branches.id')
            ->whereIn('qr_codes.id', $visibleQrIds)
            ->where('qr_scan_events.scanned_at', '>=', $from)
            ->selectRaw("COALESCE(business_branches.name, 'ყველა ფილიალი') as branch_name, COALESCE(qr_codes.placement_label, qr_codes.name) as placement, COUNT(*) as scans")
            ->groupBy('business_branches.name', 'qr_codes.placement_label', 'qr_codes.name')
            ->orderByDesc('scans')
            ->limit(20)
            ->get();

        $recentTotal = (int) $days->sum('scans');
        $totalScans = (int) $access->visibleQrCodes($request->user(), $business)->sum('scan_count');

        return view('analytics.index', compact('business', 'codes', 'days', 'totalScans', 'recentTotal', 'devices', 'referrers', 'campaigns', 'placements'));
    }
}
