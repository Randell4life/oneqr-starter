<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCatalogItem;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TbcOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function store(): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'TBC Store',
            'slug' => 'tbc-store',
            'business_type' => 'retail',
            'status' => 'active',
            'is_published' => true,
        ]);
        $item = $business->catalogItems()->create([
            'type' => 'product',
            'name' => 'Helmet',
            'slug' => 'helmet',
            'price' => '120.00',
            'currency' => 'GEL',
            'is_available' => true,
        ]);

        return [$business, $item];
    }

    private function configureTbc(): void
    {
        config([
            'tbc.enabled' => true,
            'tbc.api_key' => 'test-api-key',
            'tbc.client_id' => 'test-client',
            'tbc.client_secret' => 'test-secret',
        ]);
        Cache::forget('oneqr:tbc:access-token');
    }

    public function test_customer_can_start_tbc_card_payment_for_a_gel_order(): void
    {
        [$business, $item] = $this->store();
        $this->configureTbc();

        Http::fakeSequence()
            ->push(['access_token' => 'test-token', 'expires_in' => 3600], 200)
            ->push([
                'payId' => 'TBC-ORDER-PAY-123',
                'links' => [['rel' => 'approval_url', 'uri' => 'https://checkout.tbcbank.ge/pay/123']],
            ], 200);

        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 1]);
        $this->post(route('business.cart.checkout', $business->slug), [
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'payment_method' => 'tbc_card',
        ])->assertRedirect('https://checkout.tbcbank.ge/pay/123');

        $this->assertDatabaseHas('business_orders', [
            'business_id' => $business->id,
            'payment_method' => 'tbc_card',
            'payment_status' => 'unpaid',
            'total' => '120.00',
        ]);
        $this->assertDatabaseHas('business_order_payments', [
            'provider_payment_id' => 'TBC-ORDER-PAY-123',
            'status' => 'initiated',
            'amount' => '120.00',
            'currency' => 'GEL',
        ]);
    }

    public function test_verified_tbc_callback_marks_order_as_paid_once(): void
    {
        [$business] = $this->store();
        $order = BusinessOrder::create([
            'business_id' => $business->id,
            'order_number' => 'OQR-TEST-0001',
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'subtotal' => '120.00',
            'total' => '120.00',
            'currency' => 'GEL',
            'status' => 'new',
            'payment_method' => 'tbc_card',
            'payment_status' => 'unpaid',
        ]);
        $payment = BusinessOrderPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_order_id' => $order->id,
            'provider' => 'tbc',
            'status' => 'initiated',
            'amount' => '120.00',
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQRORDTESTPAYMENT00000001',
            'provider_payment_id' => 'TBC-ORDER-PAY-456',
            'expires_at' => now()->addMinutes(12),
        ]);
        $this->configureTbc();

        Http::fakeSequence()
            ->push(['access_token' => 'test-token', 'expires_in' => 3600], 200)
            ->push([
                'payId' => 'TBC-ORDER-PAY-456',
                'merchantPaymentId' => $payment->merchant_payment_id,
                'status' => 'Succeeded',
                'amount' => 120,
                'currency' => 'GEL',
            ], 200);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-ORDER-PAY-456'])
            ->assertOk();

        $this->assertDatabaseHas('business_order_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('business_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-ORDER-PAY-456'])
            ->assertOk();

        $this->assertDatabaseCount('business_order_payments', 1);
    }

    public function test_tbc_callback_does_not_mark_order_paid_when_amount_does_not_match(): void
    {
        [$business] = $this->store();
        $order = BusinessOrder::create([
            'business_id' => $business->id,
            'order_number' => 'OQR-TEST-0002',
            'customer_name' => 'Nino Customer',
            'customer_phone' => '+995555123456',
            'subtotal' => '120.00',
            'total' => '120.00',
            'currency' => 'GEL',
            'status' => 'new',
            'payment_method' => 'tbc_card',
            'payment_status' => 'unpaid',
        ]);
        $payment = BusinessOrderPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_order_id' => $order->id,
            'provider' => 'tbc',
            'status' => 'initiated',
            'amount' => '120.00',
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQRORDTESTPAYMENT00000002',
            'provider_payment_id' => 'TBC-ORDER-PAY-789',
        ]);
        $this->configureTbc();

        Http::fakeSequence()
            ->push(['access_token' => 'test-token', 'expires_in' => 3600], 200)
            ->push([
                'payId' => 'TBC-ORDER-PAY-789',
                'merchantPaymentId' => $payment->merchant_payment_id,
                'status' => 'Succeeded',
                'amount' => 1,
                'currency' => 'GEL',
            ], 200);

        $this->post(route('payments.tbc.callback'), ['PaymentId' => 'TBC-ORDER-PAY-789'])
            ->assertOk();

        $this->assertDatabaseHas('business_order_payments', [
            'id' => $payment->id,
            'status' => 'review',
        ]);
        $this->assertDatabaseHas('business_orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_tbc_card_payment_is_rejected_when_gateway_is_disabled(): void
    {
        [$business, $item] = $this->store();
        config(['tbc.enabled' => false]);

        $this->post(route('business.cart.add', [$business->slug, $item->id]), ['quantity' => 1]);
        $this->from(route('business.cart.show', $business->slug))
            ->post(route('business.cart.checkout', $business->slug), [
                'customer_name' => 'Nino Customer',
                'customer_phone' => '+995555123456',
                'payment_method' => 'tbc_card',
            ])->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('business_orders', 0);
    }
}
