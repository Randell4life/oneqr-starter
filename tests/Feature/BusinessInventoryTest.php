<?php

namespace Tests\\Feature;

use App\\Models\\Business;
use App\\Models\\BusinessOrder;
use App\\Models\\BusinessInventoryMovement;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class BusinessInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function store(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Inventory Store',
            'slug' => 'inventory-store',
            'business_type' => 'retail',
            'status' => 'active',
            'is_published' => true,
        ]);
        $item = $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'Helmet',
            'slug' => 'helmet',
            'sku' => 'HELM-001',
            'price' => '120.00',
            'currency' => 'GEL',
            'is_available' => true,
            'track_inventory' => true,
            'stock_quantity' => 8,
            'low_stock_threshold' => 2,
        ]);

        return [$owner, $business, $item];
    }

    public function test_checkout_deducts_stock_and_creates_a_movement(): void
    {
        [, $business, $item] = $this->store();

        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 3]);
        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect(route('business.public', $business->slug));

        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 5]);
        $this->assertDatabaseHas('business_orders', ['business_id' => $business->id]);
        $this->assertDatabaseHas('business_inventory_movements', [
            'catalog_item_id' => $item->id,
            'type' => 'sale',
            'quantity' => -3,
            'stock_before' => 8,
            'stock_after' => 5,
        ]);
        $this->assertNotNull(BusinessOrder::firstOrFail()->stock_deducted_at);
    }

    public function test_checkout_rejects_quantity_above_available_stock(): void
    {
        [, $business, $item] = $this->store();

        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 9]);
        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'payment_method' => 'cash_on_delivery',
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('business_orders', 0);
        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 8]);
    }

    public function test_cancelling_an_order_restores_stock_only_once(): void
    {
        [$owner, $business, $item] = $this->store();
        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 2]);
        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = BusinessOrder::firstOrFail();
        $this->actingAs($owner)->patch(route('business.orders.update-status', [$business, $order->id]), [
            'status' => 'cancelled',
        ])->assertRedirect();

        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 8]);
        $this->assertDatabaseHas('business_inventory_movements', [
            'business_order_id' => $order->id,
            'type' => 'return',
            'quantity' => 2,
            'stock_before' => 6,
            'stock_after' => 8,
        ]);

        $this->assertDatabaseCount('business_inventory_movements', 2);
        $this->actingAs($owner)->patch(route('business.orders.update-status', [$business, $order->id]), [
            'status' => 'cancelled',
        ])->assertRedirect();
        $this->assertDatabaseCount('business_inventory_movements', 2);
    }

    public function test_business_owner_can_restock_and_adjust_inventory(): void
    {
        [$owner, $business, $item] = $this->store();

        $this->actingAs($owner)->post(route('business.inventory.adjust', [$business, $item->id]), [
            'movement' => 'restock',
            'quantity' => 5,
            'note' => 'მომწოდებლისგან მიღება',
        ])->assertRedirect();

        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 13]);

        $this->actingAs($owner)->post(route('business.inventory.adjust', [$business, $item->id]), [
            'movement' => 'adjustment',
            'quantity' => 3,
            'note' => 'ინვენტარიზაცია',
        ])->assertRedirect();

        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 10]);
        $this->assertDatabaseCount('business_inventory_movements', 2);
    }

    public function test_non_member_cannot_change_inventory(): void
    {
        [, $business, $item] = $this->store();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->post(route('business.inventory.adjust', [$business, $item->id]), [
            'movement' => 'restock',
            'quantity' => 10,
        ])->assertForbidden();

        $this->assertDatabaseHas('business_catalog_items', ['id' => $item->id, 'stock_quantity' => 8]);
    }
}
