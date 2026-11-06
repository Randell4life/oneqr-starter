<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessReviewTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(User $owner, string $slug): Business
    {
        return Business::create([
            'owner_id' => $owner->id,
            'name' => 'Review Demo',
            'slug' => $slug,
            'business_type' => 'services',
            'status' => 'active',
            'is_published' => true,
            'timezone' => 'Asia/Tbilisi',
        ]);
    }

    public function test_public_review_is_saved_as_pending_until_business_approves_it(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'review-demo');

        $this->from(route('business.public', $business->slug))
            ->post(route('business.public.reviews.store', $business->slug), [
                'customer_name' => 'ტესტი მომხმარებელი',
                'rating' => 5,
                'comment' => 'ძალიან კარგი მომსახურება.',
                'website' => '',
            ])
            ->assertRedirect(route('business.public', $business->slug))
            ->assertSessionHas('review_status');

        $this->assertDatabaseHas('business_reviews', [
            'business_id' => $business->id,
            'customer_name' => 'ტესტი მომხმარებელი',
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_public_review_rejects_invalid_rating_and_honeypot(): void
    {
        $business = $this->publishedBusiness(User::factory()->create(), 'invalid-review-demo');

        $this->from(route('business.public', $business->slug))
            ->post(route('business.public.reviews.store', $business->slug), [
                'customer_name' => 'ტესტი',
                'rating' => 8,
                'website' => 'spam.example',
            ])
            ->assertSessionHasErrors(['rating', 'website']);

        $this->assertDatabaseCount('business_reviews', 0);
    }

    public function test_business_can_approve_only_its_own_reviews(): void
    {
        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner, 'owner-review-demo');
        $otherBusiness = $this->publishedBusiness(User::factory()->create(), 'other-review-demo');

        $ownReview = $business->reviews()->create([
            'customer_name' => 'Customer One',
            'rating' => 5,
            'status' => 'pending',
        ]);
        $foreignReview = $otherBusiness->reviews()->create([
            'customer_name' => 'Customer Two',
            'rating' => 4,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patch(route('business.reviews.status', [$business, $ownReview->id]), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('business_reviews', ['id' => $ownReview->id, 'status' => 'approved']);

        $this->actingAs($owner)
            ->patch(route('business.reviews.status', [$business, $foreignReview->id]), ['status' => 'approved'])
            ->assertNotFound();

        $this->assertDatabaseHas('business_reviews', ['id' => $foreignReview->id, 'status' => 'pending']);
    }
}
