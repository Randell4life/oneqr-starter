<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_a_ticket_and_exchange_replies_with_support(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Support Cafe',
            'slug' => 'support-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $this->actingAs($owner)->post(route('support.store'), [
            'business_id' => $business->id,
            'subject' => 'QR კოდის პრობლემა',
            'category' => 'qr',
            'priority' => 'high',
            'message' => 'QR კოდის გახსნისას შეცდომას ვიღებ.',
        ])->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($admin)->get(route('support.show', $ticket))->assertOk();
        $this->actingAs($admin)->post(route('support.reply', $ticket), [
            'message' => 'გთხოვ, გამოგვიგზავნე QR ბმული.',
        ])->assertRedirect();
        $this->assertSame('waiting_customer', $ticket->fresh()->status);

        $this->actingAs($owner)->post(route('support.reply', $ticket), [
            'message' => 'აი ბმული და დამატებითი დეტალები.',
        ])->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertDatabaseCount('support_ticket_messages', 3);
    }

    public function test_user_cannot_view_or_reply_to_another_users_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'created_by_user_id' => $owner->id,
            'subject' => 'Private support request',
            'category' => 'general',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $this->actingAs($other)->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($other)->post(route('support.reply', $ticket), [
            'message' => 'ეს ჩემი მოთხოვნა არ არის.',
        ])->assertForbidden();
    }

    public function test_business_reference_must_belong_to_the_ticket_creator(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Other Cafe',
            'slug' => 'other-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $this->actingAs($owner)->post(route('support.store'), [
            'business_id' => $business->id,
            'subject' => 'მოთხოვნა სხვის ბიზნესზე',
            'category' => 'general',
            'priority' => 'normal',
            'message' => 'სხვის ბიზნესს ვერ მივაბამ.',
        ])->assertSessionHasErrors('business_id');

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_only_platform_admin_can_access_the_support_queue_and_change_ticket_status(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $ticket = SupportTicket::query()->create([
            'created_by_user_id' => $owner->id,
            'subject' => 'Status update test',
            'category' => 'general',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $this->actingAs($owner)->get(route('platform.support.index'))->assertForbidden();
        $this->actingAs($owner)->patch(route('platform.support.update', $ticket), [
            'status' => 'resolved',
            'priority' => 'normal',
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('platform.support.index'))->assertOk();
        $this->actingAs($admin)->patch(route('platform.support.update', $ticket), [
            'status' => 'resolved',
            'priority' => 'high',
        ])->assertRedirect();

        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }
}
