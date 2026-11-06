<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartPageInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(User $owner, string $slug): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Analytics Demo',
            'slug' => $slug,
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_public_smart_page_can_record_a_valid_interaction_without_personal_data(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'interaction-demo');

        $this->postJson(route('business.public.interactions', $business->slug), ['type' => 'call'])
            ->assertAccepted()
            ->assertJson(['recorded' => true]);

        $this->assertDatabaseHas('smart_page_interactions', [
            'business_id' => $business->id,
            'interaction_type' => 'call',
        ]);
    }

    public function test_public_interaction_endpoint_rejects_unknown_types(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'invalid-interaction-demo');

        $this->postJson(route('business.public.interactions', $business->slug), ['type' => 'visitor-email'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('smart_page_interactions', 0);
    }

    public function test_unpublished_business_cannot_receive_public_interaction_events(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'unpublished-interaction-demo');
        $business->update(['is_published' => false]);

        $this->postJson(route('business.public.interactions', $business->slug), ['type' => 'call'])
            ->assertNotFound();

        $this->assertDatabaseCount('smart_page_interactions', 0);
    }

    public function test_business_analytics_only_shows_its_own_smart_page_interactions(): void
    {
        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner, 'owned-interaction-demo');
        $otherBusiness = $this->publishedBusiness(User::factory()->create(), 'other-interaction-demo');

        $business->smartPageInteractions()->create(['interaction_type' => 'call', 'occurred_at' => now()]);
        $otherBusiness->smartPageInteractions()->create(['interaction_type' => 'social', 'occurred_at' => now()]);

        $this->actingAs($owner)
            ->get(route('business.analytics', $business))
            ->assertOk()
            ->assertSee('Smart Page-ის ქმედებები')
            ->assertSee('დარეკვა')
            ->assertDontSee('სოციალური ბმული');
    }
}
