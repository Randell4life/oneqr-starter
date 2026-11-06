<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInboxTest extends TestCase
{
    use RefreshDatabase;

    private function business(User $owner, string $slug): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Inbox '.$slug,
            'slug' => $slug,
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_owner_can_view_and_update_follow_up_for_an_inquiry(): void
    {
        $owner = User::factory()->create();
        $business = $this->business($owner, 'inbox-inquiry');
        $inquiry = $business->inquiries()->create([
            'customer_name' => 'Nino',
            'customer_phone' => '+995555111222',
            'customer_email' => 'nino@example.com',
            'inquiry_type' => 'general',
            'message' => 'მინდა კონსულტაცია',
        ]);

        $this->actingAs($owner)
            ->get(route('business.inbox.index', $business))
            ->assertOk()
            ->assertSee('Nino')
            ->assertSee('მომხმარებლების ცენტრი');

        $this->actingAs($owner)
            ->patch(route('business.inbox.update', [$business, 'inquiry', $inquiry->id]), [
                'status' => 'contacted',
                'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'internal_notes' => 'დავურეკე, ელოდება შეთავაზებას.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('business_inquiries', [
            'id' => $inquiry->id,
            'status' => 'contacted',
            'internal_notes' => 'დავურეკე, ელოდება შეთავაზებას.',
        ]);
    }

    public function test_owner_can_manage_catalog_order_request_follow_up(): void
    {
        $owner = User::factory()->create();
        $business = $this->business($owner, 'inbox-order');
        $request = $business->orderRequests()->create([
            'item_name' => 'Premium service',
            'quantity' => 1,
            'customer_name' => 'Giorgi',
            'customer_phone' => '+995555333444',
            'status' => 'new',
        ]);

        $this->actingAs($owner)
            ->patch(route('business.inbox.update', [$business, 'order-request', $request->id]), [
                'status' => 'processing',
                'follow_up_at' => null,
                'internal_notes' => 'ფასი გავუგზავნე.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('business_order_requests', [
            'id' => $request->id,
            'status' => 'processing',
            'internal_notes' => 'ფასი გავუგზავნე.',
        ]);
    }

    public function test_owner_can_add_a_customer_contact_history_entry(): void
    {
        $owner = User::factory()->create();
        $business = $this->business($owner, 'inbox-history');
        $inquiry = $business->inquiries()->create([
            'customer_name' => 'Customer',
            'customer_phone' => '+995555123456',
            'inquiry_type' => 'general',
        ]);

        $this->actingAs($owner)
            ->post(route('business.inbox.interactions.store', [$business, 'inquiry', $inquiry->id]), [
                'channel' => 'phone',
                'summary' => 'დავუკავშირდი და შევთანხმდით ფასის შეთავაზებაზე.',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('business_customer_interactions', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'contactable_type' => \App\Models\BusinessInquiry::class,
            'contactable_id' => $inquiry->id,
            'channel' => 'phone',
            'summary' => 'დავუკავშირდი და შევთანხმდით ფასის შეთავაზებაზე.',
        ]);

        $this->actingAs($owner)
            ->get(route('business.inbox.index', $business))
            ->assertOk()
            ->assertSee('დავუკავშირდი და შევთანხმდით ფასის შეთავაზებაზე.');
    }

    public function test_inactive_team_member_cannot_access_customer_inbox(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $business = $this->business($owner, 'inbox-access');
        $business->memberships()->create([
            'user_id' => $staff->id,
            'role' => 'staff',
            'status' => 'invited',
        ]);

        $this->actingAs($staff)
            ->get(route('business.inbox.index', $business))
            ->assertForbidden();
    }

    public function test_staff_can_view_but_cannot_change_customer_follow_up(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $business = $this->business($owner, 'inbox-staff');
        $business->memberships()->create([
            'user_id' => $staff->id,
            'role' => 'staff',
            'status' => 'active',
        ]);
        $inquiry = $business->inquiries()->create([
            'customer_name' => 'Customer',
            'customer_phone' => '+995555999000',
            'inquiry_type' => 'general',
        ]);

        $this->actingAs($staff)
            ->get(route('business.inbox.index', $business))
            ->assertOk();

        $this->actingAs($staff)
            ->patch(route('business.inbox.update', [$business, 'inquiry', $inquiry->id]), [
                'status' => 'contacted',
                'internal_notes' => 'Unauthorized note',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('business_inquiries', [
            'id' => $inquiry->id,
            'status' => 'new',
            'internal_notes' => null,
        ]);
    }

    public function test_business_cannot_update_another_business_customer_record(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $business = $this->business($owner, 'inbox-own');
        $otherBusiness = $this->business($otherOwner, 'inbox-other');
        $foreignInquiry = $otherBusiness->inquiries()->create([
            'customer_name' => 'Foreign',
            'customer_phone' => '+995555888777',
            'inquiry_type' => 'general',
        ]);

        $this->actingAs($owner)
            ->patch(route('business.inbox.update', [$business, 'inquiry', $foreignInquiry->id]), [
                'status' => 'completed',
            ])
            ->assertNotFound();
    }
}
