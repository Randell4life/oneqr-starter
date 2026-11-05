<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMembership;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createBusiness(User $owner): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Role Test Business',
            'slug' => 'role-test-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
    }

    private function createBranch(Business $business, string $name, string $slug): BusinessBranch
    {
        return BusinessBranch::create([
            'business_id' => $business->id,
            'name' => $name,
            'slug' => $slug,
            'timezone' => 'Asia/Tbilisi',
            'is_active' => true,
        ]);
    }

    private function createQr(Business $business, BusinessBranch $branch, string $name): QrCode
    {
        return QrCode::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => $name,
            'destination_url' => 'https://example.com/'.$name,
            'category' => 'general',
        ]);
    }

    public function test_manager_can_manage_qr_codes_only_in_assigned_branch(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $business = $this->createBusiness($owner);
        $assigned = $this->createBranch($business, 'Assigned Branch', 'assigned');
        $other = $this->createBranch($business, 'Other Branch', 'other');
        $assignedQr = $this->createQr($business, $assigned, 'Assigned QR');
        $otherQr = $this->createQr($business, $other, 'Other Branch Secret QR');

        BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $manager->id,
            'branch_id' => $assigned->id,
            'invited_by' => $owner->id,
            'role' => 'manager',
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('business.qr.index', $business))
            ->assertOk()
            ->assertSee('Assigned QR')
            ->assertDontSee('Other Branch Secret QR');

        $this->actingAs($manager)
            ->put(route('business.qr.update', [$business, $otherQr->id]), [
                'name' => 'Hijacked QR',
                'destination_url' => 'https://example.com/hijacked',
                'category' => 'general',
                'is_active' => true,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('qr_codes', [
            'id' => $otherQr->id,
            'name' => 'Other Branch Secret QR',
        ]);

        $this->actingAs($manager)
            ->put(route('business.qr.update', [$business, $assignedQr->id]), [
                'name' => 'Updated Assigned QR',
                'destination_url' => 'https://example.com/updated',
                'category' => 'general',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('qr_codes', [
            'id' => $assignedQr->id,
            'name' => 'Updated Assigned QR',
        ]);
    }

    public function test_staff_can_view_assigned_qr_but_cannot_create_or_view_analytics(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $business = $this->createBusiness($owner);
        $branch = $this->createBranch($business, 'Staff Branch', 'staff-branch');
        $qr = $this->createQr($business, $branch, 'Staff QR');

        BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'branch_id' => $branch->id,
            'invited_by' => $owner->id,
            'role' => 'staff',
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('business.qr.index', $business))
            ->assertOk()
            ->assertSee('Staff QR');

        $this->actingAs($staff)
            ->get(route('business.qr.image', [$business, $qr->id]))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('business.qr.store', $business), [
                'name' => 'Unauthorized QR',
                'destination_url' => 'https://example.com/unauthorized',
                'category' => 'general',
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('business.analytics', $business))
            ->assertForbidden();
    }

    public function test_pending_invitation_does_not_grant_business_access(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();
        $business = $this->createBusiness($owner);

        BusinessMembership::create([
            'business_id' => $business->id,
            'user_id' => $invited->id,
            'invited_by' => $owner->id,
            'role' => 'manager',
            'status' => 'invited',
        ]);

        $this->actingAs($invited)
            ->get(route('business.qr.index', $business))
            ->assertForbidden();
    }
}
