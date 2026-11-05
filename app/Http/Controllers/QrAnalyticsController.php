<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class QrAnalyticsController extends Controller
{
    public function index(Request $request, Business $business): View
    {
        abort_unless($business->owner_id === $request->user()->id, 403);

        $from = now()->subDays(29)->startOfDay();
        $codes = $business->qrCodes()
            ->withCount(['scanEvents as recent_scans_count' => fn ($query) => $query->where('scanned_at', '>=', $from)])
            ->withMax('scanEvents', 'scanned_at')
            ->orderByDesc('recent_scans_count')
            ->get();

        $daily = $business->qrCodes()
            ->join('qr_scan_events', 'qr_codes.id', '=', 'qr_scan_events.qr_code_id')
            ->where('qr_scan_events.scanned_at', '>=', $from)
            ->selectRaw('DATE(qr_scan_events.scanned_at) as scan_date, COUNT(*) as scans')
            ->groupBy('scan_date')
            ->orderBy('scan_date')
            ->pluck('scans', 'scan_date');

        $days = collect(range(0, 29))->map(function (int $offset) use ($daily): array {
            $date = now()->subDays(29 - $offset)->toDateString();

            return ['date' => $date, 'label' => Carbon::parse($date)->format('d.m'), 'scans' => (int) ($daily[$date] ?? 0)];
        });

        $totalScans = (int) $business->qrCodes()->sum('scan_count');

        return view('analytics.index', compact('business', 'codes', 'days', 'totalScans'));
    }
}
