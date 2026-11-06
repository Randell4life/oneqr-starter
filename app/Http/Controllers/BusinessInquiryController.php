<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\BusinessAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessInquiryController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'inquiry_type' => ['required', Rule::in(['booking', 'consultation', 'general'])],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $business->inquiries()->create($data);

        return back()->with('inquiry_status', 'მოთხოვნა წარმატებით გაიგზავნა. ბიზნესი დაგიკავშირდებათ.');
    }

    public function updateStatus(Request $request, Business $business, int $inquiry, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);

        $record = $business->inquiries()->findOrFail($inquiry);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'completed', 'cancelled'])],
        ]);

        $record->update($data);

        return back()->with('status', 'მოთხოვნის სტატუსი განახლდა.');
    }
}
