<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TbcCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_is_disabled_without_bank_configuration(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Test Business',
            'slug' => 'test-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $plan->update(['monthly_price' => 49, 'currency' => 'GEL']);

        config(['tbc.enabled' => false]);

        $this->actingAs($owner)
            ->post(route('business.subscription.checkout', [$business, $plan]))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('business_subscription_payments', 0);
    }

    public function test_checkout_rejects_plans_without_a_price(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Test Business',
            'slug' => 'test-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $plan->update(['monthly_price' => null]);

        config(['tbc.enabled' => true]);

        $this->actingAs($owner)
            ->post(route('business.subscription.checkout', [$business, $plan]))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('business_subscription_payments', 0);
    }
}
