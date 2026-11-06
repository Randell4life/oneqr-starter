<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformBusinessSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_search_businesses_and_assign_a_plan_with_audit_record(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create(['name' => 'Business Owner']);
        $business = Business::create([
            'owner_id' => $owner->id, 'name' => 'Test Cafe', 'slug' => 'test-cafe',
            'business_type' => 'cafe', 'status' => 'active',
        ]);
        $free = SubscriptionPlan::where('slug', 'free')->firstOrFail();
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $old = BusinessSubscription::create([
            'business_id' => $business->id, 'subscription_plan_id' => $free->id,
            'status' => 'active', 'starts_at' => now()->subMonth(), 'auto_renew' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.business-subscriptions.index', ['q' => 'Test Cafe']))
            ->assertOk()->assertSee('Test Cafe')->assertSee('Business Owner');

        $this->actingAs($admin)
            ->post(route('platform.business-subscriptions.store', $business), [
                'plan_id' => $pro->id, 'duration_months' => 3, 'reason' => 'პარტნიორის საცდელი წვდომა',
            ])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertDatabaseHas('business_subscriptions', [
            'business_id' => $business->id, 'subscription_plan_id' => $pro->id, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('platform_subscription_audits', [
            'business_id' => $business->id, 'actor_user_id' => $admin->id,
            'previous_subscription_id' => $old->id, 'previous_plan_id' => $free->id,
            'new_plan_id' => $pro->id, 'duration_months' => 3,
            'reason' => 'პარტნიორის საცდელი წვდომა',
        ]);
    }

    public function test_business_owner_cannot_access_platform_subscription_management(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->get(route('platform.business-subscriptions.index'))->assertForbidden();
    }

    public function test_paid_manual_plan_assignment_requires_a_duration_and_reason(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id, 'name' => 'Validation Cafe', 'slug' => 'validation-cafe',
            'business_type' => 'cafe', 'status' => 'active',
        ]);
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('platform.business-subscriptions.index'))
            ->post(route('platform.business-subscriptions.store', $business), [
                'plan_id' => $pro->id, 'duration_months' => '', 'reason' => 'ok',
            ])
            ->assertRedirect(route('platform.business-subscriptions.index'))
            ->assertSessionHasErrors(['duration_months', 'reason']);

        $this->assertDatabaseMissing('platform_subscription_audits', ['business_id' => $business->id]);
    }

    public function test_free_plan_assignment_is_permanent_and_audited(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id, 'name' => 'Free Cafe', 'slug' => 'free-cafe',
            'business_type' => 'cafe', 'status' => 'active',
        ]);
        $free = SubscriptionPlan::where('slug', 'free')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.business-subscriptions.store', $business), [
                'plan_id' => $free->id, 'reason' => 'დაბრუნება უფასო გეგმაზე',
            ])
            ->assertRedirect()->assertSessionHas('status');

        $subscription = BusinessSubscription::query()->where('business_id', $business->id)
            ->where('status', 'active')->latest('starts_at')->firstOrFail();
        $this->assertSame($free->id, $subscription->subscription_plan_id);
        $this->assertNull($subscription->ends_at);
        $this->assertDatabaseHas('platform_subscription_audits', [
            'business_id' => $business->id, 'new_subscription_id' => $subscription->id,
            'duration_months' => null,
        ]);
    }
}
