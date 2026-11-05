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

class TbcCallbackActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_success_callback_activates_the_paid_plan_once(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Callback Business',
            'slug' => 'callback-business',
            'business_type' => 'retail',
            'status' => 'active',
        ]);
        $free = SubscriptionPlan::where('slug', 'free')->firstOrFail();
        $pro = SubscriptionPlan::where('slug', 'pro')->firstOrFail();

        $business->subscriptions()->create([
            'subscription_plan_id' => $free->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => false,
        ]);

        $payment = BusinessSubscriptionPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'provider' => 'tbc',
            'status' => 'initiated',
            'amount' => 49,
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQRTESTPAYMENT000000000001',
            'provider_payment_id' => 'TBC-PAY-123',
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
                'payId' => 'TBC-PAY-123',
                'merchantPaymentId' => $payment->merchant_payment_id,
                'status' => 'Succeeded',
                'amount' => 49,
                'currency' => 'GEL',
            ], 200);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-PAY-123'])
            ->assertOk();

        $this->assertDatabaseHas('business_subscription_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('business_subscriptions', [
            'business_id' => $business->id,
            'subscription_plan_id' => $free->id,
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('business_subscriptions', [
            'business_id' => $business->id,
            'subscription_plan_id' => $pro->id,
            'status' => 'active',
        ]);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-PAY-123'])
            ->assertOk();

        $this->assertDatabaseCount('business_subscriptions', 2);
    }
}
