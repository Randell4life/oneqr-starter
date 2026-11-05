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
            ])
            ->assertRedirect(route('business.qr.index', $business));

        $qr = $business->qrCodes()->firstOrFail();

        $this->actingAs($user)
            ->get(route('business.qr.image', [$business, $qr->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
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
