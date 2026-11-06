<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\QrCode as BusinessQrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_qr_and_download_svg(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'business_type' => 'retail',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('business.qr.store', $business), [
                'name' => 'Main menu',
                'destination_url' => 'https://example.com/menu',
                'category' => 'menu',
                'placement_label' => 'მაგიდა 04',
                'campaign_name' => 'ზაფხულის მენიუ',
            ])
            ->assertRedirect(route('business.qr.index', $business));

        $qr = $business->qrCodes()->firstOrFail();
        $this->assertSame('მაგიდა 04', $qr->placement_label);
        $this->assertSame('ზაფხულის მენიუ', $qr->campaign_name);

        $this->actingAs($user)
            ->get(route('business.qr.image', [$business, $qr->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
    }


    public function test_business_owner_can_create_a_table_order_qr_from_the_qr_form(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Table Cafe',
            'slug' => 'table-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get(route('business.qr.index', $business))
            ->assertOk()
            ->assertSee('მაგიდის შეკვეთა');

        $this->actingAs($owner)
            ->post(route('business.qr.store', $business), [
                'name' => 'მაგიდა 07',
                'destination_url' => route('business.public', $business->slug),
                'category' => 'table',
                'placement_label' => 'მაგიდა 07',
            ])
            ->assertRedirect(route('business.qr.index', $business));

        $qr = $business->qrCodes()->firstOrFail();
        $this->assertSame('table', $qr->category);
        $this->assertSame('მაგიდა 07', $qr->placement_label);

        $this->get(route('qr.redirect', $qr->public_id))
            ->assertRedirect(route('business.public', $business->slug));

        $this->assertSame('მაგიდა 07', session('oneqr_table_context.label'));
    }

    public function test_business_owner_can_view_device_and_referrer_analytics(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Analytics Shop',
            'slug' => 'analytics-shop',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $qr = BusinessQrCode::create([
            'business_id' => $business->id,
            'name' => 'Main QR',
            'destination_url' => 'https://example.com',
            'scan_count' => 1,
            'placement_label' => 'მაგიდა 12',
            'campaign_name' => 'Black Friday',
        ]);
        $qr->scanEvents()->create([
            'scanned_at' => now(),
            'device_type' => 'mobile',
            'referrer_host' => 'instagram.com',
        ]);

        $this->actingAs($owner)
            ->get(route('business.analytics', $business))
            ->assertOk()
            ->assertSee('მოწყობილობები')
            ->assertSee('მობილური')
            ->assertSee('instagram.com')
            ->assertSee('Black Friday')
            ->assertSee('მაგიდა 12');
    }

    public function test_owner_can_filter_qr_codes_by_campaign_and_placement(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Filter Shop',
            'slug' => 'filter-shop',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        BusinessQrCode::create([
            'business_id' => $business->id,
            'name' => 'Table QR',
            'destination_url' => 'https://example.com/table',
            'placement_label' => 'მაგიდა 04',
            'campaign_name' => 'Summer 2026',
        ]);
        BusinessQrCode::create([
            'business_id' => $business->id,
            'name' => 'Entrance QR',
            'destination_url' => 'https://example.com/entrance',
            'placement_label' => 'შესასვლელი',
            'campaign_name' => 'Winter 2026',
        ]);

        $this->actingAs($owner)
            ->get(route('business.qr.index', [$business, 'campaign' => 'Summer 2026', 'placement' => '04']))
            ->assertOk()
            ->assertSee('Table QR')
            ->assertDontSee('Entrance QR');
    }

    public function test_owner_can_open_a_printable_qr_card(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Print Shop',
            'slug' => 'print-shop',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $qr = BusinessQrCode::create([
            'business_id' => $business->id,
            'name' => 'Table 04',
            'destination_url' => 'https://example.com/menu',
            'placement_label' => 'მაგიდა 04',
            'campaign_name' => 'საზაფხულო კამპანია',
        ]);

        $this->actingAs($owner)
            ->get(route('business.qr.print', [$business, $qr->id]))
            ->assertOk()
            ->assertSee('მაგიდა 04')
            ->assertSee('Print Shop')
            ->assertSee(route('qr.redirect', $qr->public_id));
    }

    public function test_other_user_cannot_view_or_download_business_qr(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Private Shop',
            'slug' => 'private-shop',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $qr = BusinessQrCode::create([
            'business_id' => $business->id,
            'name' => 'Private QR',
            'destination_url' => 'https://example.com',
        ]);

        $this->actingAs($other)
            ->get(route('business.qr.image', [$business, $qr->id]))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('business.qr.index', $business))
            ->assertForbidden();
    }
}
