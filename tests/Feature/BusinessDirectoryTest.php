<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_directory_shows_only_published_active_businesses_without_login(): void
    {
        $owner = User::factory()->create();

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Tbilisi Coffee',
            'slug' => 'tbilisi-coffee',
            'business_type' => 'restaurant',
            'status' => 'active',
            'city' => 'Tbilisi',
            'is_published' => true,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Draft Shop',
            'slug' => 'draft-shop',
            'business_type' => 'retail',
            'status' => 'active',
            'is_published' => false,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Suspended Hotel',
            'slug' => 'suspended-hotel',
            'business_type' => 'hotel',
            'status' => 'suspended',
            'is_published' => true,
        ]);

        $this->get(route('business.directory'))
            ->assertOk()
            ->assertSee('Tbilisi Coffee')
            ->assertDontSee('Draft Shop')
            ->assertDontSee('Suspended Hotel');
    }

    public function test_directory_search_and_category_city_filters_work(): void
    {
        $owner = User::factory()->create();

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Old Town Cafe',
            'slug' => 'old-town-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'city' => 'Tbilisi',
            'tagline' => 'Specialty coffee',
            'is_published' => true,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Batumi Boutique',
            'slug' => 'batumi-boutique',
            'business_type' => 'retail',
            'status' => 'active',
            'city' => 'Batumi',
            'is_published' => true,
        ]);

        $this->get(route('business.directory', [
            'q' => 'coffee',
            'category' => 'restaurant',
            'city' => 'Tbilisi',
        ]))
            ->assertOk()
            ->assertSee('Old Town Cafe')
            ->assertDontSee('Batumi Boutique');
    }

    public function test_directory_links_to_existing_public_business_profile(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Open Studio',
            'slug' => 'open-studio',
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
        ]);

        $this->get(route('business.directory'))
            ->assertOk()
            ->assertSee(route('business.public', $business->slug), false);
    }

    public function test_directory_shows_popular_businesses_ranked_by_favorites(): void
    {
        $owner = User::factory()->create();
        $first = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Popular Cafe',
            'slug' => 'popular-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
        ]);
        $second = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Another Cafe',
            'slug' => 'another-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
        ]);

        $first->favoritedBy()->attach(User::factory()->create()->id);
        $first->favoritedBy()->attach(User::factory()->create()->id);
        $second->favoritedBy()->attach(User::factory()->create()->id);

        $response = $this->get(route('business.directory'))->assertOk();
        $response->assertSee('პოპულარული ბიზნესები')
            ->assertSee('Popular Cafe')
            ->assertSee('2 რჩეული');
        $this->assertLessThan(
            strpos($response->getContent(), 'Another Cafe'),
            strpos($response->getContent(), 'Popular Cafe')
        );
    }

    public function test_directory_category_chips_show_only_categories_with_published_active_businesses(): void
    {
        $owner = User::factory()->create();
        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Published Cafe',
            'slug' => 'published-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
        ]);
        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Hidden Shop',
            'slug' => 'hidden-shop',
            'business_type' => 'retail',
            'status' => 'suspended',
            'is_published' => true,
        ]);

        $this->get(route('business.directory'))
            ->assertOk()
            ->assertSee('value="restaurant"', false)
            ->assertDontSee('value="retail"', false)
            ->assertDontSee('category=retail');
    }


    public function test_directory_map_includes_only_public_businesses_with_coordinates(): void
    {
        $owner = User::factory()->create();

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Mapped Cafe',
            'slug' => 'mapped-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'city' => 'Tbilisi',
            'is_published' => true,
            'latitude' => 41.7151,
            'longitude' => 44.8271,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Unmapped Cafe',
            'slug' => 'unmapped-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Hidden Mapped Cafe',
            'slug' => 'hidden-mapped-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => false,
            'latitude' => 41.7151,
            'longitude' => 44.8271,
        ]);

        $this->get(route('business.directory'))
            ->assertOk()
            ->assertSee('Mapped Cafe')
            ->assertDontSee('Unmapped Cafe')
            ->assertDontSee('Hidden Mapped Cafe');
    }

    public function test_nearby_directory_search_filters_businesses_using_coordinates(): void
    {
        $owner = User::factory()->create();

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Nearby Cafe',
            'slug' => 'nearby-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
            'latitude' => 41.7151,
            'longitude' => 44.8271,
        ]);

        Business::create([
            'owner_id' => $owner->id,
            'name' => 'Distant Cafe',
            'slug' => 'distant-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
            'latitude' => 42.7000,
            'longitude' => 41.7000,
        ]);

        $this->get(route('business.directory', [
            'nearby' => 1,
            'lat' => 41.7151,
            'lng' => 44.8271,
        ]))
            ->assertOk()
            ->assertSee('Nearby Cafe')
            ->assertDontSee('Distant Cafe');
    }

}
