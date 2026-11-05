<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_business_profile_is_not_public(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Hidden Cafe',
            'slug' => 'hidden-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        $this->get(route('business.public', $business->slug))->assertNotFound();
    }

    public function test_published_business_profile_is_public(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Visible Cafe',
            'slug' => 'visible-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'tagline' => 'Fresh every day',
            'is_published' => true,
        ]);

        $this->get(route('business.public', $business->slug))
            ->assertOk()
            ->assertSee('Visible Cafe')
            ->assertSee('Fresh every day');
    }

    public function test_only_business_owner_can_edit_profile(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Owner Cafe',
            'slug' => 'owner-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        $this->actingAs($other)
            ->get(route('business.profile.edit', $business))
            ->assertForbidden();
    }

    public function test_owner_can_publish_business_profile(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'My Cafe',
            'slug' => 'my-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->put(route('business.profile.update', $business), [
                'name' => 'My Cafe',
                'tagline' => 'Welcome',
                'description' => 'A friendly local cafe.',
                'business_type' => 'restaurant',
                'email' => 'hello@example.com',
                'phone' => '+995555123456',
                'website' => 'https://example.com',
                'address' => 'Rustaveli Avenue',
                'city' => 'Tbilisi',
                'accent_color' => '#8B5CF6',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'is_published' => true,
            'city' => 'Tbilisi',
        ]);

        $this->get(route('business.public', $business->slug))
            ->assertOk()
            ->assertSee('My Cafe');
    }
}
