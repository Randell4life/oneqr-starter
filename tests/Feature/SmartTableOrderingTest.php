<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartTableOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_qr_context_is_saved_on_the_customer_order(): void
    {
        $owner = User::factory()->create();
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Table Cafe', 'slug' => 'table-cafe', 'business_type' => 'cafe', 'status' => 'active', 'is_published' => true]);
        $item = $business->catalogItems()->create(['type' => 'product', 'name' => 'Coffee', 'slug' => 'coffee', 'price' => '8.00', 'currency' => 'GEL', 'is_available' => true]);
        $qr = QrCode::create(['business_id' => $business->id, 'name' => 'მაგიდა 4', 'category' => 'table', 'placement_label' => 'მაგიდა 4', 'destination_url' => route('business.public', $business->slug), 'is_active' => true]);

        $this->get(route('qr.redirect', $qr->public_id))->assertRedirect(route('business.public', $business->slug));
        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 2]);
        $this->post(route('business.cart.checkout', $business->slug), ['customer_name' => 'Test Customer', 'customer_phone' => '555123456', 'payment_method' => 'cash_on_delivery'])
            ->assertRedirect(route('business.public', $business->slug));

        $this->assertDatabaseHas('business_orders', ['business_id' => $business->id, 'table_label' => 'მაგიდა 4', 'total' => '16.00']);
        $this->assertNull(session('oneqr_table_context'));
    }
}
