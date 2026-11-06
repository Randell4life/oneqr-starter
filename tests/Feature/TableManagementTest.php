<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessOrder;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableManagementTest extends TestCase
{
    use RefreshDatabase;

    private function setupBusiness(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id, 'name' => 'Table Management Cafe',
            'slug' => 'table-management-cafe', 'business_type' => 'cafe', 'status' => 'active',
        ]);

        return [$owner, $business];
    }

    public function test_owner_can_view_table_qrs_and_active_orders_by_table_label(): void
    {
        [$owner, $business] = $this->setupBusiness();
        QrCode::create([
            'business_id' => $business->id, 'name' => 'Table 04 QR',
            'destination_url' => 'https://example.com/menu', 'category' => 'table',
            'placement_label' => 'მაგიდა 04',
        ]);
        BusinessOrder::create([
            'business_id' => $business->id, 'order_number' => 'TABLE-ORDER-04',
            'customer_name' => 'Guest', 'customer_phone' => '555123456',
            'subtotal' => 20, 'total' => 20, 'currency' => 'GEL',
            'status' => 'processing', 'payment_method' => 'cash_on_delivery',
            'payment_status' => 'unpaid', 'table_label' => 'მაგიდა 04',
        ]);

        $this->actingAs($owner)->get(route('business.tables.index', $business))
            ->assertOk()->assertSee('მაგიდების მართვა')->assertSee('მაგიდა 04')
            ->assertSee('TABLE-ORDER-04')->assertSee('შეკვეთა აქტიურია');
    }

    public function test_table_management_does_not_show_qrs_from_another_business(): void
    {
        [$owner, $business] = $this->setupBusiness();
        $otherBusiness = Business::create([
            'owner_id' => User::factory()->create()->id, 'name' => 'Other Cafe',
            'slug' => 'other-cafe', 'business_type' => 'cafe', 'status' => 'active',
        ]);
        QrCode::create([
            'business_id' => $otherBusiness->id, 'name' => 'Secret Table',
            'destination_url' => 'https://example.com', 'category' => 'table',
            'placement_label' => 'Secret Table 9',
        ]);

        $this->actingAs($owner)->get(route('business.tables.index', $business))
            ->assertOk()->assertDontSee('Secret Table 9');
    }

    public function test_other_user_cannot_open_business_table_management(): void
    {
        [, $business] = $this->setupBusiness();

        $this->actingAs(User::factory()->create())
            ->get(route('business.tables.index', $business))->assertForbidden();
    }
}
