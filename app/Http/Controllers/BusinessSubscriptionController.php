<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscriptionPayment;
use App\Support\PlanAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessSubscriptionController extends Controller
{
    public function index(Request $request, Business $business, PlanAccess $access): View
    {
        abort_unless((int) $business->owner_id === (int) $request->user()->id, 403);

        $plans = \App\Models\SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $subscription = $access->subscription($business);
        $latestSubscription = $business->subscriptions()->with('plan')->latest('starts_at')->first();
        $currentPlan = $access->plan($business);
        $usage = $access->usage($business);
        $payments = BusinessSubscriptionPayment::query()
            ->with('plan')
            ->where('business_id', $business->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('business.subscription', compact(
            'business',
            'plans',
            'subscription',
            'latestSubscription',
            'currentPlan',
            'usage',
            'payments'
        ));
    }
}
