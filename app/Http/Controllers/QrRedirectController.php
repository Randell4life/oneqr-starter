<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrRedirectController extends Controller
{
    public function __invoke(Request $request, string $publicId): RedirectResponse
    {
        $qrCode = QrCode::query()
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->firstOrFail();

        DB::transaction(function () use ($request, $qrCode): void {
            DB::table('qr_codes')->where('id', $qrCode->id)->update([
                'scan_count' => DB::raw('scan_count + 1'),
                'last_scanned_at' => now(),
            ]);

            $agent = strtolower($request->userAgent() ?? '');
            $device = match (true) {
                str_contains($agent, 'ipad'), str_contains($agent, 'tablet') => 'tablet',
                str_contains($agent, 'mobile'), str_contains($agent, 'android'), str_contains($agent, 'iphone') => 'mobile',
                default => 'desktop',
            };

            $referrerHost = null;
            if ($request->headers->has('referer')) {
                $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
                $referrerHost = is_string($host) ? substr($host, 0, 190) : null;
            }

            $qrCode->scanEvents()->create([
                'scanned_at' => now(),
                'device_type' => $device,
                'referrer_host' => $referrerHost,
            ]);
        });

        if (strtolower((string) $qrCode->category) === 'table') {
            $label = trim((string) ($qrCode->placement_label ?: $qrCode->name));
            if ($label !== '') {
                $request->session()->put('oneqr_table_context', [
                    'business_id' => $qrCode->business_id,
                    'label' => mb_substr($label, 0, 80),
                ]);
            }
        } else {
            $context = $request->session()->get('oneqr_table_context');
            if (($context['business_id'] ?? null) === $qrCode->business_id) {
                $request->session()->forget('oneqr_table_context');
            }
        }

        return redirect()->away($qrCode->destination_url);
    }
}
