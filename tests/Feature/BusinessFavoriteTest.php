<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(string $slug): Business
    {
        return Business::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Business '.$slug,
            'slug' => $slug,
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_authenticated_user_can_save_a_published_business_only_once(): void
    {
        $user = User::factory()->create();
        $business = $this->publishedBusiness('favorite-demo');

        $this->actingAs($user)
            ->post(route('business.public.favorite.store', $business->slug))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('business.public.favorite.store', $business->slug))
            ->assertRedirect();

        $this->assertDatabaseCount('business_favorites', 1);
        $this->assertTrue($user->fresh()->favoriteBusinesses()->whereKey($business->id)->exists());
    }

    public function test_guest_is_redirected_to_login_when_saving_a_business(): void
    {
        $business = $this->publishedBusiness('guest-favorite-demo');

        $this->post(route('business.public.favorite.store', $business->slug))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('business_favorites', 0);
    }

    public function test_user_can_remove_a_business_from_their_favorites(): void
    {
        $user = User::factory()->create();
        $business = $this->publishedBusiness('remove-favorite-demo');
        $user->favoriteBusinesses()->attach($business->id);

        $this->actingAs($user)
            ->delete(route('business.public.favorite.destroy', $business->slug))
            ->assertRedirect();

        $this->assertDatabaseCount('business_favorites', 0);
    }

    public function test_favorites_are_private_to_each_user_and_hidden_when_business_is_unpublished(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $published = $this->publishedBusiness('visible-favorite-demo');
        $unpublished = $this->publishedBusiness('hidden-favorite-demo');
        $unpublished->update(['is_published' => false]);

        $user->favoriteBusinesses()->attach([$published->id, $unpublished->id]);
        $anotherUser->favoriteBusinesses()->attach($published->id);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Business visible-favorite-demo')
            ->assertDontSee('Business hidden-favorite-demo');

        $this->assertSame(2, $user->fresh()->favoriteBusinesses()->count());
        $this->assertSame(1, $anotherUser->fresh()->favoriteBusinesses()->count());
    }
}
