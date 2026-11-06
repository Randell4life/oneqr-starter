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
