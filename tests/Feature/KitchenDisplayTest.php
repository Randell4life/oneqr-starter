<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function businessAndOrder(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Kitchen Cafe',
            'slug' => 'kitchen-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
            'is_published' => true,
        ]);

        $order = BusinessOrder::create([
            'business_id' => $business->id,
            'order_number' => 'OQR-KITCHEN-001',
            'customer_name' => 'Test Customer',
            'customer_phone' => '555123456',
            'subtotal' => '18.00',
            'total' => '18.00',
            'currency' => 'GEL',
            'status' => 'new',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'unpaid',
            'table_label' => 'მაგიდა 3',
        ]);

        return [$owner, $business, $order];
    }

    public function test_owner_can_open_kitchen_screen_and_see_active_table_order(): void
    {
        [$owner, $business] = $this->businessAndOrder();

        $this->actingAs($owner)
            ->get(route('business.kitchen', $business))
            ->assertOk()
            ->assertSee('სამზარეულოს ეკრანი')
            ->assertSee('OQR-KITCHEN-001')
            ->assertSee('მაგიდა 3')
            ->assertSee('ახალი შეკვეთები');
    }

    public function test_user_from_another_business_cannot_open_kitchen_screen(): void
    {
        [, $business] = $this->businessAndOrder();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('business.kitchen', $business))
            ->assertForbidden();
    }

    public function test_kitchen_screen_excludes_completed_and_cancelled_orders(): void
    {
        [$owner, $business, $order] = $this->businessAndOrder();
        $order->update(['status' => 'completed']);

        $this->actingAs($owner)
            ->get(route('business.kitchen', $business))
            ->assertOk()
            ->assertDontSee('OQR-KITCHEN-001')
            ->assertSee('აქტიური ჯამი');
    }
}
