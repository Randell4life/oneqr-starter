<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCatalogItem;
use App\Models\BusinessOrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(User $owner): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Catalog Cafe',
            'slug' => 'catalog-cafe',
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
        ]);
    }

    public function test_public_business_page_shows_available_catalog_items_only(): void
    {
        $business = $this->publishedBusiness(User::factory()->create());
        $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'House Coffee',
            'slug' => 'house-coffee',
            'price' => 8.50,
            'currency' => 'GEL',
            'is_available' => true,
        ]);
        $business->catalogItems()->create([
            'type' => 'service',
            'name' => 'Hidden Special',
            'slug' => 'hidden-special',
            'is_available' => false,
        ]);

        $this->get(route('business.public', $business->slug))
            ->assertOk()
            ->assertSee('House Coffee')
            ->assertDontSee('Hidden Special');
    }

    public function test_customer_can_submit_order_request_for_published_catalog_item(): void
    {
        $business = $this->publishedBusiness(User::factory()->create());
        $item = $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'House Coffee',
            'slug' => 'house-coffee',
            'price' => 8.50,
            'currency' => 'GEL',
            'is_available' => true,
        ]);

        $this->post(route('business.catalog.order', [$business->slug, $item->id]), [
            'customer_name' => 'Nino',
            'customer_phone' => '+995555123456',
            'customer_email' => 'nino@example.com',
            'quantity' => 2,
            'message' => 'Please call before delivery.',
        ])->assertRedirect();

        $this->assertDatabaseHas('business_order_requests', [
            'business_id' => $business->id,
            'catalog_item_id' => $item->id,
            'item_name' => 'House Coffee',
            'quantity' => 2,
            'customer_phone' => '+995555123456',
            'status' => 'new',
        ]);
    }

    public function test_unavailable_item_cannot_receive_order_requests(): void
    {
        $business = $this->publishedBusiness(User::factory()->create());
        $item = $business->catalogItems()->create([
            'type' => 'service',
            'name' => 'Unavailable Service',
            'slug' => 'unavailable-service',
            'is_available' => false,
        ]);

        $this->post(route('business.catalog.order', [$business->slug, $item->id]), [
            'customer_name' => 'Nino',
            'customer_phone' => '+995555123456',
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertDatabaseCount('business_order_requests', 0);
    }

    public function test_only_business_manager_can_manage_catalog(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = $this->publishedBusiness($owner);

        $this->actingAs($other)
            ->post(route('business.catalog.store', $business), [
                'type' => 'product',
                'name' => 'Unauthorized item',
                'currency' => 'GEL',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('business_catalog_items', 0);
    }


    public function test_free_plan_blocks_eleventh_catalog_item(): void
    {
        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);

        for ($i = 1; $i <= 10; $i++) {
            $business->catalogItems()->create([
                'type' => 'product',
                'name' => 'Product '.$i,
                'slug' => 'product-'.$i,
                'currency' => 'GEL',
                'is_available' => true,
            ]);
        }

        $this->actingAs($owner)->post(route('business.catalog.store', $business), [
            'type' => 'product',
            'name' => 'Eleventh Product',
            'currency' => 'GEL',
            'is_available' => '1',
        ])->assertSessionHasErrors('plan');

        $this->assertDatabaseMissing('business_catalog_items', [
            'business_id' => $business->id,
            'name' => 'Eleventh Product',
        ]);
    }

    public function test_owner_can_create_catalog_item_and_view_orders(): void
    {
        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);

        $this->actingAs($owner)->post(route('business.catalog.store', $business), [
            'type' => 'service',
            'name' => 'Premium Wash',
            'description' => 'Full service wash',
            'price' => '45.00',
            'currency' => 'GEL',
            'is_available' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('business_catalog_items', [
            'business_id' => $business->id,
            'name' => 'Premium Wash',
            'type' => 'service',
            'price' => '45.00',
        ]);

        $this->get(route('business.catalog.index', $business))
            ->assertOk()
            ->assertSee('Premium Wash')
            ->assertSee('მომხმარებლების მოთხოვნები');
    }
}
