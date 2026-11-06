<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInquiryTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(User $owner, string $slug): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Inquiry Demo',
            'slug' => $slug,
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_public_smart_page_inquiry_is_saved_for_the_published_business(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'inquiry-demo');

        $this->from(route('business.public', $business->slug))
            ->post(route('business.public.inquiries.store', $business->slug), [
                'customer_name' => 'ტესტი მომხმარებელი',
                'customer_phone' => '+995555123456',
                'customer_email' => 'customer@example.com',
                'inquiry_type' => 'booking',
                'preferred_date' => now()->addDays(3)->toDateString(),
                'message' => 'მინდა კონსულტაცია.',
            ])
            ->assertRedirect(route('business.public', $business->slug))
            ->assertSessionHas('inquiry_status');

        $this->assertDatabaseHas('business_inquiries', [
            'business_id' => $business->id,
            'customer_phone' => '+995555123456',
            'inquiry_type' => 'booking',
            'status' => 'new',
        ]);
    }

    public function test_public_inquiry_rejects_invalid_type_and_past_preferred_date(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'invalid-inquiry-demo');

        $this->from(route('business.public', $business->slug))
            ->post(route('business.public.inquiries.store', $business->slug), [
                'customer_name' => 'ტესტი',
                'customer_phone' => '+995555123456',
                'inquiry_type' => 'spam',
                'preferred_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['inquiry_type', 'preferred_date']);

        $this->assertDatabaseCount('business_inquiries', 0);
    }

    public function test_business_can_update_only_its_own_inquiry_status(): void
    {
        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner, 'owner-inquiry-demo');
        $otherBusiness = $this->publishedBusiness(User::factory()->create(), 'other-inquiry-demo');

        $ownInquiry = $business->inquiries()->create([
            'customer_name' => 'Owner Customer',
            'customer_phone' => '+995555000001',
            'inquiry_type' => 'general',
        ]);
        $foreignInquiry = $otherBusiness->inquiries()->create([
            'customer_name' => 'Other Customer',
            'customer_phone' => '+995555000002',
            'inquiry_type' => 'general',
        ]);

        $this->actingAs($owner)
            ->patch(route('business.inquiries.status', [$business, $ownInquiry->id]), ['status' => 'contacted'])
            ->assertRedirect();

        $this->assertDatabaseHas('business_inquiries', ['id' => $ownInquiry->id, 'status' => 'contacted']);

        $this->actingAs($owner)
            ->patch(route('business.inquiries.status', [$business, $foreignInquiry->id]), ['status' => 'completed'])
            ->assertNotFound();

        $this->assertDatabaseHas('business_inquiries', ['id' => $foreignInquiry->id, 'status' => 'new']);
    }
}
