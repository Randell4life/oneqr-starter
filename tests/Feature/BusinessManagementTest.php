<?php

namespace Tests\\Feature;

use App\\Models\\Business;
use App\\Models\\BusinessBranch;
use App\\Models\\BusinessMembership;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_branch_and_invite_registered_user(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Demo Business',
            'slug' => 'demo-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->post(route('business.branches.store', $business), [
                'name' => 'თბილისის ფილიალი',
                'city' => 'თბილისი',
                'timezone' => 'Asia/Tbilisi',
            ])
            ->assertRedirect();

        $branch = $business->branches()->firstOrFail();
        $this->assertTrue($branch->is_primary);

        $this->actingAs($owner)
            ->post(route('business.members.invite', $business), [
                'email' => $staff->email,
                'role' => 'staff',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect();

        $membership = BusinessMembership::where('business_id', $business->id)
            ->where('user_id', $staff->id)
            ->firstOrFail();

        $this->assertSame('invited', $membership->status);
        $this->assertSame($branch->id, $membership->branch_id);

        $this->actingAs($staff)
            ->get(route('team.invitations'))
            ->assertOk()
            ->assertSee('Demo Business');

        $this->actingAs($staff)
            ->post(route('team.invitations.accept', $membership->id))
            ->assertRedirect(route('team.invitations'));

        $this->assertDatabaseHas('business_memberships', [
            'id' => $membership->id,
            'status' => 'active',
        ]);
    }

    public function test_non_owner_cannot_manage_business_or_invite_staff(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $staff = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Private Business',
            'slug' => 'private-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);

        $this->actingAs($other)
            ->get(route('business.manage', $business))
            ->assertForbidden();

        $this->actingAs($other)
            ->post(route('business.members.invite', $business), [
                'email' => $staff->email,
                'role' => 'manager',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('business_memberships', 0);
    }

    public function test_primary_branch_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Branch Business',
            'slug' => 'branch-business',
            'business_type' => 'services',
            'status' => 'active',
        ]);
        $branch = BusinessBranch::create([
            'business_id' => $business->id,
            'name' => 'Main branch',
            'slug' => 'main',
            'timezone' => 'Asia/Tbilisi',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->delete(route('business.branches.destroy', [$business, $branch->id]))
            ->assertStatus(422);

        $this->assertDatabaseHas('business_branches', ['id' => $branch->id]);
    }
}
