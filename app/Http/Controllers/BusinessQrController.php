<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessQrController extends Controller
{
    public function index(Request $request, Business $business): View
    {
        $this->authorizeBusiness($request, $business);

        return view('qr.index', [
            'business' => $business,
            'qrCodes' => $business->qrCodes()->latest()->paginate(12),
        ]);
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'destination_url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'category' => ['required', Rule::in(['general', 'menu', 'product', 'contact', 'booking', 'review', 'campaign'])],
        ]);

        $business->qrCodes()->create($data);

        return redirect()->route('business.qr.index', $business)->with('status', 'QR კოდი წარმატებით შეიქმნა.');
    }

    public function update(Request $request, Business $business, int $qrCode): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $record = $business->qrCodes()->findOrFail($qrCode);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'destination_url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'category' => ['required', Rule::in(['general', 'menu', 'product', 'contact', 'booking', 'review', 'campaign'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $record->update($data);

        return back()->with('status', 'QR კოდი განახლდა.');
    }

    public function destroy(Request $request, Business $business, int $qrCode): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);
        $business->qrCodes()->findOrFail($qrCode)->delete();

        return back()->with('status', 'QR კოდი წაიშალა.');
    }

    private function authorizeBusiness(Request $request, Business $business): void
    {
        abort_unless($business->owner_id === $request->user()->id, 403);
    }
}
