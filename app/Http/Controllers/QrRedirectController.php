<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QrRedirectController extends Controller
{
    public function __invoke(string $publicId): RedirectResponse
    {
        $qrCode = QrCode::query()
            ->where('public_id', $publicId)
            ->where('is_active', true)
            ->firstOrFail();

        DB::table('qr_codes')
            ->where('id', $qrCode->id)
            ->update([
                'scan_count' => DB::raw('scan_count + 1'),
                'last_scanned_at' => now(),
            ]);

        return redirect()->away($qrCode->destination_url);
    }
}
