<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCartTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithItems(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Cart Store',
            'slug' => 'cart-store',
            'business_type' => 'retail',
            'status' => 'active',
            'is_published' => true,
        ]);

        $first = $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'Helmet',
            'slug' => 'helmet',
            'price' => '120.00',
            'currency' => 'GEL',
            'is_available' => true,
        ]);
        $second = $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'Gloves',
            'slug' => 'gloves',
            'price' => '45.50',
            'currency' => 'GEL',
            'is_available' => true,
        ]);

        return [$owner, $business, $first, $second];
    }

    public function test_customer_can_add_multiple_items_and_submit_one_order(): void
    {
        [, $business, $helmet, $gloves] = $this->businessWithItems();

        $this->post(route('business.cart.add', [$business->slug, $helmet->id]), ['quantity' => 1])
            ->assertRedirect();
        $this->post(route('business.cart.add', [$business->slug, $gloves->id]), ['quantity' => 2])
            ->assertRedirect();

        $this->get(route('business.cart.show', $business->slug))
            ->assertOk()
            ->assertSee('Helmet')
            ->assertSee('Gloves')
            ->assertSee('211.00');

        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'customer_email' => 'nino@example.com',
            'customer_address' => 'Tbilisi',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect(route('business.public', $business->slug))
            ->assertSessionHas('order_success');

        $this->assertDatabaseCount('business_orders', 1);
        $order = BusinessOrder::with('items')->firstOrFail();
        $this->assertSame('211.00', $order->total);
        $this->assertCount(2, $order->items);
        $this->assertDatabaseHas('business_order_items', [
            'business_order_id' => $order->id,
            'item_name' => 'Gloves',
            'quantity' => 2,
            'line_total' => '91.00',
        ]);

        $this->get(route('business.cart.show', $business->slug))->assertSee('კალათა ცარიელია');
    }

    public function test_cart_rejects_items_with_a_different_currency(): void
    {
        [, $business, $helmet, $gloves] = $this->businessWithItems();
        $gloves->update(['currency' => 'USD']);

        $this->post(route('business.cart.add', [$business->slug, $helmet->id]), ['quantity' => 1]);
        $this->from(route('business.public', $business->slug))
            ->post(route('business.cart.add', [$business->slug, $gloves->id]), ['quantity' => 1])
            ->assertSessionHasErrors('cart');
    }

    public function test_cart_cannot_checkout_if_item_becomes_unavailable(): void
    {
        [, $business, $helmet] = $this->businessWithItems();
        $this->post(route('business.cart.add', [$business->slug, $helmet->id]), ['quantity' => 1]);
        $helmet->update(['is_available' => false]);

        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'payment_method' => 'cash_on_delivery',
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('business_orders', 0);
    }

    public function test_business_order_management_is_restricted_to_business_members(): void
    {
        [$owner, $business] = $this->businessWithItems();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('business.orders.index', $business))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('business.orders.index', $business))
            ->assertOk()
            ->assertSee('შეკვეთები');
    }

    public function test_unpublished_business_cart_is_not_accessible(): void
    {
        [, $business] = $this->businessWithItems();
        $business->update(['is_published' => false]);

        $this->get(route('business.cart.show', $business->slug))->assertNotFound();
    }
}
