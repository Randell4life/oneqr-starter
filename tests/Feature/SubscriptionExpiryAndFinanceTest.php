<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\BusinessSubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionExpiryAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_command_downgrades_an_ended_subscription_to_free(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Expiry Test',
            'slug' => 'expiry-test',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $free = SubscriptionPlan::where('slug', 'free')->firstOrFail();
        $subscription = BusinessSubscription::create([
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subSecond(),
            'auto_renew' => true,
        ]);

        Artisan::call('subscriptions:expire');

        $this->assertSame('expired', $subscription->fresh()->status);
        $this->assertFalse($subscription->fresh()->auto_renew);
        $this->assertDatabaseHas('business_subscriptions', [
            'business_id' => $business->id,
            'subscription_plan_id' => $free->id,
            'status' => 'active',
        ]);
    }

    public function test_expiry_command_keeps_a_subscription_that_has_not_ended(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Future Test',
            'slug' => 'future-test',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $subscription = BusinessSubscription::create([
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        Artisan::call('subscriptions:expire');

        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_platform_admin_can_view_finance_and_export_csv(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Finance Test',
            'slug' => 'finance-test',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        BusinessSubscriptionPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'subscription_plan_id' => $plan->id,
            'provider' => 'tbc',
            'status' => 'paid',
            'amount' => 49,
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQRFINANCETEST0000000000001',
            'provider_payment_id' => 'TBC-FINANCE-TEST',
            'verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('platform.subscription-finance.index'))
            ->assertOk()
            ->assertSee('გამოწერების ფინანსები')
            ->assertSee('Finance Test')
            ->assertSee('49.00 GEL');

        $this->actingAs($admin)
            ->get(route('platform.subscription-finance.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_business_owner_cannot_view_platform_finance(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('platform.subscription-finance.index'))
            ->assertForbidden();
    }
}
