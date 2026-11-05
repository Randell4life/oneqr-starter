<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'დაწყებისთვის — ძირითადი QR ფუნქციები.',
                'monthly_price' => 0,
                'limits' => ['qr_codes' => 5, 'branches' => 1, 'team_members' => 2],
                'features' => ['QR კოდების შექმნა', 'ძირითადი ანალიტიკა'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'მცირე ბიზნესებისთვის.',
                'monthly_price' => null,
                'limits' => ['qr_codes' => 30, 'branches' => 3, 'team_members' => 5],
                'features' => ['მეტი QR კოდი', 'რამდენიმე ფილიალი', 'გუნდის მართვა'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'მზარდი ბიზნესებისთვის.',
                'monthly_price' => null,
                'limits' => ['qr_codes' => 150, 'branches' => 10, 'team_members' => 20],
                'features' => ['გაფართოებული ლიმიტები', 'ფილიალების მართვა', 'გუნდის მართვა', 'ანალიტიკა'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'business',
                'name' => 'Business',
                'description' => 'დიდი ქსელებისა და მრავალფილიალიანი ბიზნესებისთვის.',
                'monthly_price' => null,
                'limits' => ['qr_codes' => null, 'branches' => null, 'team_members' => null],
                'features' => ['შეუზღუდავი რესურსები', 'მრავალფილიალიანი მართვა', 'პრიორიტეტული მხარდაჭერა'],
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'monthly_price' => $plan['monthly_price'],
                    'currency' => 'GEL',
                    'limits' => json_encode($plan['limits'], JSON_THROW_ON_ERROR),
                    'features' => json_encode($plan['features'], JSON_THROW_ON_ERROR),
                    'is_active' => true,
                    'sort_order' => $plan['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $freePlanId = DB::table('subscription_plans')->where('slug', 'free')->value('id');

        foreach (DB::table('businesses')->whereNull('deleted_at')->pluck('id') as $businessId) {
            $hasSubscription = DB::table('business_subscriptions')
                ->where('business_id', $businessId)
                ->whereIn('status', ['active', 'trialing'])
                ->exists();

            if (! $hasSubscription) {
                DB::table('business_subscriptions')->insert([
                    'business_id' => $businessId,
                    'subscription_plan_id' => $freePlanId,
                    'status' => 'active',
                    'starts_at' => $now,
                    'auto_renew' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep subscription history and plan definitions intact on rollback.
    }
};
