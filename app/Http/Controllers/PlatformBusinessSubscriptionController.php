<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\PlatformSubscriptionAudit;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformBusinessSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePlatformAdmin($request);
        $search = trim((string) $request->query('q', ''));

        $businesses = Business::query()
            ->with(['owner:id,name,email'])
            ->with(['subscriptions' => function ($query): void {
                $query->with('plan')
                    ->whereIn('status', ['active', 'trialing', 'canceled'])
                    ->where(fn ($active) => $active->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                    ->where(fn ($trial) => $trial->where('status', '!=', 'trialing')->orWhereNull('trial_ends_at')->orWhere('trial_ends_at', '>', now()))
                    ->latest('starts_at');
            }])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $match) use ($search): void {
                    $match->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('owner', fn (Builder $owner) => $owner
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->withCount(['subscriptions as subscription_history_count'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $plans = SubscriptionPlan::query()->orderBy('sort_order')->get();
        $summary = [
            'businesses' => Business::query()->count(),
            'paid' => BusinessSubscription::query()
                ->whereIn('status', ['active', 'trialing', 'canceled'])
                ->whereHas('plan', fn (Builder $plan) => $plan->where('slug', '!=', 'free'))
                ->where(fn (Builder $active) => $active->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->count(),
        ];

        return view('platform.business-subscriptions', compact('businesses', 'plans', 'search', 'summary'));
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($data['plan_id']);
        $isFreePlan = $plan->slug === 'free';

        if (! $isFreePlan && empty($data['duration_months'])) {
            return back()->withErrors([
                'duration_months' => 'ფასიანი გეგმის მინიჭებისას მიუთითე მოქმედების ვადა (1–36 თვე).',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $business, $plan, $data, $isFreePlan): void {
            $lockedBusiness = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            $currentSubscriptions = BusinessSubscription::query()
                ->where('business_id', $lockedBusiness->id)
                ->whereIn('status', ['active', 'trialing', 'canceled'])
                ->where(fn ($active) => $active->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->where(fn ($trial) => $trial->where('status', '!=', 'trialing')->orWhereNull('trial_ends_at')->orWhere('trial_ends_at', '>', now()))
                ->orderByDesc('starts_at')
                ->lockForUpdate()
                ->get();

            $previous = $currentSubscriptions->first();
            foreach ($currentSubscriptions as $subscription) {
                $subscription->update(['status' => 'expired', 'auto_renew' => false]);
            }

            $startsAt = now();
            $endsAt = $isFreePlan ? null : $startsAt->copy()->addMonths((int) $data['duration_months']);
            $newSubscription = BusinessSubscription::query()->create([
                'business_id' => $lockedBusiness->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => false,
                'payment_provider' => null,
                'provider_subscription_id' => null,
            ]);

            PlatformSubscriptionAudit::query()->create([
                'business_id' => $lockedBusiness->id,
                'actor_user_id' => $request->user()->id,
                'previous_subscription_id' => $previous?->id,
                'new_subscription_id' => $newSubscription->id,
                'previous_plan_id' => $previous?->subscription_plan_id,
                'new_plan_id' => $plan->id,
                'duration_months' => $isFreePlan ? null : (int) $data['duration_months'],
                'reason' => $data['reason'],
            ]);
        });

        return back()->with('status', 'ბიზნესის გამოწერის გეგმა განახლდა. ცვლილება აღირიცხა ადმინისტრაციულ ისტორიაში.');
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);
    }
}
