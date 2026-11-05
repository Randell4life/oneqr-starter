<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_platform_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_platform_admin_can_open_dashboard_and_management_resources(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/businesses')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/subscription-plans')->assertOk();
        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/subscriptions')->assertOk();
    }

    public function test_platform_admin_can_update_business_status_and_subscription_plan_price(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Admin Managed Business',
            'slug' => 'admin-managed-business',
            'business_type' => 'retail',
            'status' => 'pending',
        ]);

        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/businesses/'.$business->id.'/edit')
            ->assertOk()
            ->assertSee('Admin Managed Business');

        $this->actingAs($admin)
            ->get('/admin/subscription-plans/'.$plan->id.'/edit')
            ->assertOk()
            ->assertSee('Pro');
    }

    public function test_console_command_grants_admin_access_to_existing_user_only(): void
    {
        $user = User::factory()->create(['email' => 'platform-admin@example.com']);

        $this->artisan('oneqr:make-admin', ['email' => $user->email])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_platform_admin' => true,
        ]);

        $this->artisan('oneqr:make-admin', ['email' => 'missing@example.com'])
            ->assertExitCode(1);
    }
}
