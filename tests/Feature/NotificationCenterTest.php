<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Notifications\BusinessActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function businessFor(User $owner, string $slug): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Notification '.$slug,
            'slug' => $slug,
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_business_inquiry_creates_a_database_notification_for_its_owner(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-inquiry');

        $this->post(route('business.public.inquiries.store', $business->slug), [
            'customer_name' => 'Customer',
            'customer_phone' => '+995555123456',
            'inquiry_type' => 'general',
        ])->assertRedirect();

        $notification = $owner->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('inquiry.created', $notification->data['event']);
        $this->assertSame('ახალი მოთხოვნა', $notification->data['title']);
        $this->assertSame($business->id, $notification->data['business_id']);
    }

    public function test_notification_center_lists_only_the_authenticated_users_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-private');

        $owner->notify(new BusinessActivityNotification(
            'order.created', 'ახალი შეკვეთა', 'შეკვეთა მიღებულია.',
            route('dashboard'), $business->id, $business->name
        ));
        $other->notify(new BusinessActivityNotification(
            'order.created', 'სხვისი შეკვეთა', 'პირადი შეტყობინება.',
            route('dashboard'), $business->id, $business->name
        ));

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('ახალი შეკვეთა')
            ->assertDontSee('სხვისი შეკვეთა');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-isolation');

        $other->notify(new BusinessActivityNotification(
            'order.created', 'სხვა მომხმარებლის შეტყობინება', 'შეტყობინება.',
            route('dashboard'), $business->id, $business->name
        ));
        $notification = $other->notifications()->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_business_notifications_are_sent_to_owner_and_active_team_members_only_once(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $inactive = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-team');

        $business->memberships()->create([
            'user_id' => $manager->id,
            'role' => 'manager',
            'status' => 'active',
        ]);
        $business->memberships()->create([
            'user_id' => $inactive->id,
            'role' => 'staff',
            'status' => 'invited',
        ]);

        app(\App\Services\BusinessActivityNotifier::class)->notify(
            $business,
            'order.created',
            'ახალი შეკვეთა',
            'შეკვეთა მიღებულია.',
            route('business.catalog.index', $business),
        );

        $this->assertSame(1, $owner->fresh()->notifications()->count());
        $this->assertSame(1, $manager->fresh()->notifications()->count());
        $this->assertSame(0, $inactive->fresh()->notifications()->count());
    }

    public function test_unsafe_notification_redirects_fall_back_to_notification_center(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-safe-link');

        $owner->notify(new BusinessActivityNotification(
            'test.event', 'უსაფრთხო ბმულის ტესტი', 'ტესტი.',
            'javascript:alert(1)', $business->id, $business->name
        ));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner, 'notify-read-all');
        $owner->notify(new BusinessActivityNotification(
            'review.created', 'ახალი შეფასება', 'შეფასება მიღებულია.',
            route('dashboard'), $business->id, $business->name
        ));

        $this->actingAs($owner)
            ->patch(route('notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }
}
