<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscriptionPayment;
use App\Models\BusinessOrderPayment;
use App\Models\BusinessOrderPayment;
use App\Models\SubscriptionPlan;
use App\Services\Payments\TbcCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BusinessSubscriptionPaymentController extends Controller
{
    public function checkout(
        Request $request,
        Business $business,
        SubscriptionPlan $plan,
        TbcCheckout $tbc
    ): RedirectResponse {
        $this->authorizeOwner($request, $business);

        abort_unless($plan->is_active, 404);

        if (! config('tbc.enabled')) {
            throw ValidationException::withMessages([
                'payment' => 'TBC გადახდა ჯერ არ არის კონფიგურირებული. დაამატე მერჩანტის მონაცემები .env ფაილში.',
            ]);
        }

        if ($plan->monthly_price === null || (float) $plan->monthly_price <= 0 || $plan->currency !== 'GEL') {
            throw ValidationException::withMessages([
                'payment' => 'ამ გეგმის ფასი ჯერ განსაზღვრული არ არის.',
            ]);
        }

        $payment = BusinessSubscriptionPayment::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'subscription_plan_id' => $plan->id,
            'provider' => 'tbc',
            'status' => 'pending',
            'amount' => $plan->monthly_price,
            'currency' => 'GEL',
            'merchant_payment_id' => 'OQR'.Str::upper(Str::random(24)),
            'expires_at' => now()->addMinutes(12),
        ]);

        try {
            $checkout = $tbc->createPayment(
                $payment->merchant_payment_id,
                (float) $payment->amount,
                route('payments.tbc.return', $payment->uuid, true),
                route('payments.tbc.callback', [], true),
                $request->ip()
            );

            $payment->update([
                'provider_payment_id' => $checkout['pay_id'],
                'checkout_url' => $checkout['checkout_url'],
                'status' => 'initiated',
            ]);
        } catch (RuntimeException $exception) {
            report($exception);
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'TBC payment initialization failed.',
            ]);

            throw ValidationException::withMessages([
                'payment' => 'ბანკთან დაკავშირება ვერ მოხერხდა. სცადე მოგვიანებით.',
            ]);
        }

        return redirect()->away($payment->checkout_url);
    }

    public function callback(Request $request, TbcCheckout $tbc, BusinessOrderPaymentController $orderPayments): \Illuminate\Http\Response
    {
        $payId = $request->input('PaymentId') ?? $request->input('paymentId');

        if (! is_string($payId) || $payId === '') {
            return response('Invalid payment reference', 400);
        }

        $orderPayment = BusinessOrderPayment::query()\n            ->where('provider', 'tbc')\n            ->where('provider_payment_id', $payId)\n            ->exists();\n\n        if ($orderPayment) {\n            return $orderPayments->callbackPayment($payId, $tbc);\n        }\n\n        $orderPayment = BusinessOrderPayment::query()
            ->where('provider', 'tbc')
            ->where('provider_payment_id', $payId)
            ->exists();

        if ($orderPayment) {
            return $orderPayments->callbackPayment($payId, $tbc);
        }

        $payment = BusinessSubscriptionPayment::query()
            ->where('provider', 'tbc')
            ->where('provider_payment_id', $payId)
            ->first();

        if (! $payment) {
            return response('Payment not found', 404);
        }

        $this->verifyAndActivate($payment, $tbc);
        $payment->refresh();

        if (! in_array($payment->status, ['paid', 'failed', 'expired', 'review'], true)) {
            return response('TBC payment verification temporarily unavailable', 503);
        }

        return response('OK', 200);
    }

    public function returned(string $uuid, TbcCheckout $tbc): RedirectResponse
    {
        $payment = BusinessSubscriptionPayment::where('uuid', $uuid)->firstOrFail();

        if ($payment->provider_payment_id && ! in_array($payment->status, ['paid', 'failed', 'expired'], true)) {
            $this->verifyAndActivate($payment, $tbc);
            $payment->refresh();
        }

        if ($payment->status === 'paid') {
            return redirect()->route('business.subscription', $payment->business_id)
                ->with('success', 'გადახდა წარმატებულია. გამოწერა გააქტიურდა.');
        }

        return redirect()->route('business.subscription', $payment->business_id)
            ->with('error', 'გადახდა ჯერ არ დადასტურებულა. სტატუსი: '.$payment->status);
    }

    private function verifyAndActivate(BusinessSubscriptionPayment $payment, TbcCheckout $tbc): void
    {
        if ($payment->status === 'paid' || ! $payment->provider_payment_id) {
            return;
        }

        try {
            $details = $tbc->paymentDetails($payment->provider_payment_id);
        } catch (RuntimeException $exception) {
            report($exception);
            return;
        }

        if (($details['payId'] ?? null) !== $payment->provider_payment_id) {
            return;
        }

        $status = $details['status'] ?? null;

        if (in_array($status, ['Failed', 'Expired'], true)) {
            $payment->update([
                'status' => strtolower($status),
                'failure_reason' => 'Payment was not completed.',
            ]);
            return;
        }

        if ($status !== 'Succeeded') {
            return;
        }

        $providerAmount = is_array($details['amount'] ?? null)
            ? ($details['amount']['total'] ?? -1)
            : ($details['amount'] ?? -1);
        $amount = number_format((float) $providerAmount, 2, '.', '');
        $expectedAmount = number_format((float) $payment->amount, 2, '.', '');

        $currency = is_array($details['amount'] ?? null)
            ? ($details['amount']['currency'] ?? null)
            : ($details['currency'] ?? null);
        $merchantPaymentId = $details['merchantPaymentId'] ?? $payment->merchant_payment_id;

        if (
            $amount !== $expectedAmount
            || $currency !== $payment->currency
            || $merchantPaymentId !== $payment->merchant_payment_id
        ) {
            report(new RuntimeException('TBC payment amount, currency or merchant reference mismatch for payment '.$payment->id));
            $payment->update([
                'status' => 'review',
                'failure_reason' => 'Provider payment details did not match the expected order.',
            ]);
            return;
        }

        DB::transaction(function () use ($payment): void {
            $lockedPayment = BusinessSubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === 'paid') {
                return;
            }

            $lockedPayment->update([
                'status' => 'paid',
                'verified_at' => now(),
                'failure_reason' => null,
            ]);

            $business = $lockedPayment->business()->lockForUpdate()->firstOrFail();

            $business->subscriptions()
                ->whereIn('status', ['active', 'trialing', 'canceled'])
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->update([
                    'status' => 'superseded',
                    'auto_renew' => false,
                    'updated_at' => now(),
                ]);

            $business->subscriptions()->create([
                'subscription_plan_id' => $lockedPayment->subscription_plan_id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => false,
                'payment_provider' => 'tbc',
                'provider_subscription_id' => $lockedPayment->provider_payment_id,
            ]);
        });
    }

    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) $request->user()->id, 403);
    }
}
