<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\QrCode as BusinessQrCode;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrImageController extends Controller
{
    public function show(Request $request, Business $business, int $qrCode): Response
    {
        abort_unless($business->owner_id === $request->user()->id, 403);

        $record = $business->qrCodes()->findOrFail($qrCode);
        $target = route('qr.redirect', $record->public_id);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 8,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
        ]);

        $svg = (new QRCode($options))->render($target);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="oneqr-'.$record->public_id.'.svg"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
