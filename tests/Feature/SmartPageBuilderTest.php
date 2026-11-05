<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SmartPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartPageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function businessFor(User $owner, string $slug = 'smart-demo'): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Smart Demo',
            'slug' => $slug,
            'business_type' => 'restaurant',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_owner_can_create_edit_and_publish_a_smart_page(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner);

        $this->actingAs($owner)->post(route('business.smart-pages.store', $business), [
            'name' => 'Morning Menu',
            'kind' => 'default',
        ])->assertRedirect();

        $page = $business->smartPages()->where('slug', 'morning-menu')->firstOrFail();

        $this->actingAs($owner)->put(route('business.smart-pages.update', [$business, $page]), [
            'name' => 'Morning Menu',
            'is_published' => '1',
            'blocks' => [
                ['type' => 'heading', 'title' => 'დილა', 'text' => 'საუზმის მენიუ', 'is_visible' => '1'],
                ['type' => 'text', 'title' => 'შეთავაზება', 'text' => 'ყავა და კრუასანი', 'is_visible' => '1'],
            ],
        ])->assertRedirect();

        $this->assertTrue($page->fresh()->is_published);
        $this->assertDatabaseHas('smart_page_blocks', ['smart_page_id' => $page->id, 'type' => 'heading', 'sort_order' => 0]);
        $this->get(route('business.public', $business->slug))->assertOk()->assertSee('საუზმის მენიუ');
    }

    public function test_other_business_owner_cannot_manage_another_business_smart_page(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $business = $this->businessFor($owner, 'private-smart-demo');
        $page = $business->smartPages()->create([
            'name' => 'Private Page',
            'slug' => 'private-page',
            'kind' => 'default',
            'is_default' => true,
            'is_published' => true,
        ]);

        $this->actingAs($other)
            ->get(route('business.smart-pages.edit', [$business, $page]))
            ->assertForbidden();

        $this->actingAs($other)
            ->put(route('business.smart-pages.update', [$business, $page]), [
                'name' => 'Hijack',
                'is_published' => '1',
                'blocks' => [['type' => 'text', 'text' => 'Nope', 'is_visible' => '1']],
            ])->assertForbidden();
    }

    public function test_schedule_only_uses_published_pages_and_falls_back_to_default(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner, 'schedule-demo');
        $default = $business->smartPages()->create([
            'name' => 'Default',
            'slug' => 'default',
            'kind' => 'default',
            'is_default' => true,
            'is_published' => true,
        ]);
        $default->blocks()->create(['type' => 'heading', 'title' => 'Default', 'content' => ['text' => 'ძირითადი გვერდი'], 'sort_order' => 0]);

        $draft = $business->smartPages()->create([
            'name' => 'Draft',
            'slug' => 'draft',
            'kind' => 'variant',
            'is_published' => false,
        ]);
        $business->smartPageSchedules()->create([
            'smart_page_id' => $draft->id,
            'name' => 'Draft schedule',
            'weekdays' => [],
            'priority' => 99,
            'is_active' => true,
        ]);

        $this->get(route('business.public', $business->slug))->assertOk()->assertSee('ძირითადი გვერდი');
    }

    public function test_published_schedule_switches_page_in_business_timezone(): void
    {
        $owner = User::factory()->create();
        $business = $this->businessFor($owner, 'time-demo');
        $default = $business->smartPages()->create([
            'name' => 'Default',
            'slug' => 'default',
            'kind' => 'default',
            'is_default' => true,
            'is_published' => true,
        ]);
        $default->blocks()->create(['type' => 'heading', 'title' => 'Default', 'content' => ['text' => 'ძირითადი გვერდი'], 'sort_order' => 0]);

        $evening = $business->smartPages()->create([
            'name' => 'Evening',
            'slug' => 'evening',
            'kind' => 'variant',
            'is_published' => true,
        ]);
        $evening->blocks()->create(['type' => 'heading', 'title' => 'Evening', 'content' => ['text' => 'საღამოს მენიუ'], 'sort_order' => 0]);

        $business->smartPageSchedules()->create([
            'smart_page_id' => $evening->id,
            'name' => 'Evening hours',
            'weekdays' => [4],
            'starts_at' => '17:00:00',
            'ends_at' => '23:00:00',
            'priority' => 10,
            'is_active' => true,
        ]);

        $this->travelTo(now()->setTimezone('Asia/Tbilisi')->startOfWeek()->addDays(3)->setTime(19, 0));
        $this->get(route('business.public', $business->slug))->assertOk()->assertSee('საღამოს მენიუ');
        $this->travelBack();
    }
}
