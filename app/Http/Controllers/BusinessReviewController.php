<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\BusinessAccess;
use App\Services\BusinessActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessReviewController extends Controller
{
    public function store(Request $request, string $slug, BusinessActivityNotifier $notifier): RedirectResponse
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'prohibited'],
        ]);

        unset($data['website']);
        $data['status'] = 'pending';
        $business->reviews()->create($data);
        $notifier->notify(
            $business,
            'review.created',
            'ახალი შეფასება',
            $data['customer_name'].'-მა დატოვა ახალი შეფასება ('.$data['rating'].'/5).',
            route('business.catalog.index', $business),
        );

        return back()->with('review_status', 'შეფასება მიღებულია და გამოქვეყნდება ბიზნესის დადასტურების შემდეგ.');
    }

    public function updateStatus(Request $request, Business $business, int $review, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);

        $record = $business->reviews()->findOrFail($review);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $record->update($data);

        return back()->with('status', 'შეფასების სტატუსი განახლდა.');
    }
}
