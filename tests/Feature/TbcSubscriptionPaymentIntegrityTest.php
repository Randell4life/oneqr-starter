<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TbcSubscriptionPaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_status_without_merchant_reference_is_not_accepted(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Integrity Business',
            'slug' => 'integrity-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $payment = BusinessSubscriptionPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'provider' => 'tbc',
            'status' => 'initiated',
            'amount' => 49,
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQRINTEGRITYTEST00000000001',
            'provider_payment_id' => 'TBC-PAY-INTEGRITY',
            'expires_at' => now()->addMinutes(12),
        ]);

        config([
            'tbc.api_key' => 'test-api-key',
            'tbc.client_id' => 'test-client',
            'tbc.client_secret' => 'test-secret',
        ]);
        Cache::forget('oneqr:tbc:access-token');

        Http::fakeSequence()
            ->push(['access_token' => 'test-token', 'expires_in' => 3600], 200)
            ->push([
                'payId' => 'TBC-PAY-INTEGRITY',
                'status' => 'Succeeded',
                'amount' => 49,
                'currency' => 'GEL',
            ], 200);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-PAY-INTEGRITY'])
            ->assertOk();

        $this->assertDatabaseHas('business_subscription_payments', [
            'id' => $payment->id,
            'status' => 'review',
        ]);
        $this->assertDatabaseMissing('business_subscriptions', [
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'status' => 'active',
        ]);
    }
}
