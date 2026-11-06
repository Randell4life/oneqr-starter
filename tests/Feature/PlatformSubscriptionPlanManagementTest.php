<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_update_plan_price_limits_and_features(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.subscription-plans.update', $plan), [
                'name' => 'Pro Plus',
                'description' => 'განახლებული პაკეტი',
                'monthly_price' => '59.90',
                'currency' => 'GEL',
                'is_active' => '1',
                'sort_order' => 3,
                'limits' => [
                    'qr_codes' => '200',
                    'branches' => '12',
                    'team_members' => '25',
                    'catalog_items' => '500',
                ],
                'features_text' => "გაფართოებული ანალიტიკა\nპრიორიტეტული მხარდაჭერა",
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $plan->refresh();
        $this->assertSame('Pro Plus', $plan->name);
        $this->assertSame('59.90', $plan->monthly_price);
        $this->assertSame(200, $plan->limits['qr_codes']);
        $this->assertSame(500, $plan->limits['catalog_items']);
        $this->assertSame(['გაფართოებული ანალიტიკა', 'პრიორიტეტული მხარდაჭერა'], $plan->features);
    }

    public function test_business_owner_cannot_access_platform_plan_management(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('platform.subscription-plans.index'))
            ->assertForbidden();
    }

    public function test_free_plan_cannot_be_deactivated(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $plan = SubscriptionPlan::where('slug', 'free')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.subscription-plans.update', $plan), [
                'name' => 'Free',
                'description' => 'უფასო',
                'monthly_price' => '0',
                'currency' => 'GEL',
                'sort_order' => 1,
                'limits' => [
                    'qr_codes' => '5',
                    'branches' => '1',
                    'team_members' => '2',
                    'catalog_items' => '',
                ],
                'features_text' => 'ძირითადი QR',
            ])
            ->assertRedirect();

        $this->assertTrue($plan->fresh()->is_active);
    }
}
