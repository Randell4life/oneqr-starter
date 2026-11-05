<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMembership;
use App\Models\QrCode;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithFreePlan(User $owner): Business
    {
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Plan Limit Business',
            'slug' => 'plan-limit-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);

        $free = SubscriptionPlan::where('slug', 'free')->firstOrFail();

        $business->subscriptions()->create([
            'subscription_plan_id' => $free->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => false,
        ]);

        return $business;
    }

    public function test_new_business_starts_on_free_plan(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Owner',
            'email' => 'new-owner@example.com',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
            'business_name' => 'New Business',
            'business_type' => 'retail',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('subscription_plans', ['slug' => 'free']);
        $this->assertDatabaseHas('business_subscriptions', [
            'status' => 'active',
            'subscription_plan_id' => SubscriptionPlan::where('slug', 'free')->value('id'),
        ]);
    }

    public function test_free_plan_blocks_sixth_qr_code(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessWithFreePlan($owner);

        for ($i = 1; $i <= 5; $i++) {
            $business->qrCodes()->create([
                'name' => 'QR '.$i,
                'destination_url' => 'https://example.com/'.$i,
                'category' => 'general',
            ]);
        }

        $this->actingAs($owner)->post(route('business.qr.store', $business), [
            'name' => 'Sixth QR',
            'destination_url' => 'https://example.com/sixth',
            'category' => 'general',
        ])->assertSessionHasErrors('plan');

        $this->assertDatabaseMissing('qr_codes', ['name' => 'Sixth QR']);
    }

    public function test_free_plan_blocks_second_branch(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessWithFreePlan($owner);

        $business->branches()->create([
            'name' => 'First Branch',
            'slug' => 'first-branch',
            'timezone' => 'Asia/Tbilisi',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('business.branches.store', $business), [
            'name' => 'Second Branch',
            'timezone' => 'Asia/Tbilisi',
        ])->assertSessionHasErrors('plan');

        $this->assertDatabaseMissing('business_branches', ['slug' => 'second-branch']);
    }

    public function test_pending_invites_count_towards_team_limit(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessWithFreePlan($owner);

        foreach (range(1, 2) as $i) {
            $invitee = User::factory()->create();
            BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $invitee->id,
                'invited_by' => $owner->id,
                'role' => 'staff',
                'status' => 'invited',
            ]);
        }

        $third = User::factory()->create();

        $this->actingAs($owner)->post(route('business.members.invite', $business), [
            'email' => $third->email,
            'role' => 'staff',
        ])->assertSessionHasErrors('plan');

        $this->assertDatabaseMissing('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $third->id,
        ]);
    }

    public function test_owner_can_view_plan_and_usage_page(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessWithFreePlan($owner);

        $this->actingAs($owner)
            ->get(route('business.subscription', $business))
            ->assertOk()
            ->assertSee('Free')
            ->assertSee('QR კოდები');
    }
}
