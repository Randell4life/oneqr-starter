<?php

namespace App\Support;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Validation\ValidationException;

class PlanAccess
{
    public function subscription(Business $business): ?BusinessSubscription
    {
        return $business->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trialing', 'canceled'])
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }

    public function plan(Business $business): SubscriptionPlan
    {
        return $this->subscription($business)?->plan
            ?? SubscriptionPlan::where('slug', 'free')->firstOrFail();
    }

    public function usage(Business $business): array
    {
        return [
            'qr_codes' => $business->qrCodes()->count(),
            'branches' => $business->branches()->count(),
            'team_members' => $business->memberships()->whereIn('status', ['active', 'invited'])->count(),
            'catalog_items' => $business->catalogItems()->count(),
        ];
    }

    public function limit(Business $business, string $feature): ?int
    {
        $value = $this->plan($business)->limits[$feature] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function assertCanCreate(Business $business, string $feature): void
    {
        $limit = $this->limit($business, $feature);

        if ($limit === null) {
            return;
        }

        $usage = $this->usage($business)[$feature] ?? 0;

        if ($usage >= $limit) {
            $labels = [
                'qr_codes' => 'QR კოდების',
                'branches' => 'ფილიალების',
                'team_members' => 'აქტიური თანამშრომლების',
                'catalog_items' => 'კატალოგის ჩანაწერების',
            ];

            $label = $labels[$feature] ?? 'რესურსის';
            $plan = $this->plan($business);

            throw ValidationException::withMessages([
                'plan' => "გეგმა „{$plan->name}“ {$label} ლიმიტს ({$limit}) აღწევს. გეგმის გასაზრდელად გახსენი გამოწერის გვერდი.",
            ]);
        }
    }
}
