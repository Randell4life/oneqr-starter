<?php

namespace App\Http\Controllers;

use App\Models\BusinessOrder;
use App\Models\BusinessOrderPayment;
use App\Services\Payments\TbcCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BusinessOrderPaymentController extends Controller
{
    public function callbackPayment(string $payId, TbcCheckout $tbc): \Illuminate\Http\Response
    {
        if ($payId === '') {
            return response('Invalid payment reference', 400);
        }

        $payment = BusinessOrderPayment::query()
            ->where('provider', 'tbc')
            ->where('provider_payment_id', $payId)
            ->first();

        if (! $payment) {
            return response('Payment not found', 404);
        }

        $this->verify($payment, $tbc);
        $payment->refresh();

        if (! in_array($payment->status, ['paid', 'failed', 'expired', 'review'], true)) {
            return response('TBC payment verification temporarily unavailable', 503);
        }

        return response('OK', 200);
    }

    public function returned(string $uuid, TbcCheckout $tbc): RedirectResponse
    {
        $payment = BusinessOrderPayment::query()
            ->with('order.business')
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($payment->provider_payment_id && ! in_array($payment->status, ['paid', 'failed', 'expired', 'review'], true)) {
            $this->verify($payment, $tbc);
            $payment->refresh();
        }

        $order = $payment->order;
        $business = $order->business;

        if ($payment->status === 'paid') {
            return redirect()->route('business.public', $business->slug)
                ->with('order_success', [
                    'number' => $order->order_number,
                    'total' => number_format((float) $order->total, 2).' '.$order->currency,
                ])
                ->with('payment_success', 'ბარათით გადახდა წარმატებით შესრულდა.');
        }

        if (in_array($payment->status, ['failed', 'expired'], true)) {
            return redirect()->route('business.public', $business->slug)
                ->with('order_success', [
                    'number' => $order->order_number,
                    'total' => number_format((float) $order->total, 2).' '.$order->currency,
                ])
                ->with('payment_error', 'გადახდა ვერ დასრულდა. შეკვეთა შენარჩუნებულია; დაუკავშირდი ბიზნესს.');
        }

        return redirect()->route('business.public', $business->slug)
            ->with('payment_pending', 'გადახდის დადასტურება ჯერ მიმდინარეობს. შეკვეთის სტატუსი ავტომატურად განახლდება.');
    }

    private function verify(BusinessOrderPayment $payment, TbcCheckout $tbc): void
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

        $providerStatus = $details['status'] ?? null;

        if (in_array($providerStatus, ['Failed', 'Expired'], true)) {
            $status = strtolower($providerStatus);
            DB::transaction(function () use ($payment, $status): void {
                $locked = BusinessOrderPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') {
                    return;
                }
                $locked->update([
                    'status' => $status,
                    'failure_reason' => 'Payment was not completed.',
                ]);
                $locked->order()->update(['payment_status' => $status]);
            });
            return;
        }

        if ($providerStatus !== 'Succeeded') {
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
        $merchantPaymentId = $details['merchantPaymentId'] ?? null;

        if (
            $amount !== $expectedAmount
            || $currency !== $payment->currency
            || $merchantPaymentId !== $payment->merchant_payment_id
        ) {
            report(new RuntimeException('TBC order payment details mismatch for payment '.$payment->id));
            $payment->update([
                'status' => 'review',
                'failure_reason' => 'Provider payment details did not match the expected order.',
            ]);
            return;
        }

        DB::transaction(function () use ($payment): void {
            $locked = BusinessOrderPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return;
            }

            $locked->update([
                'status' => 'paid',
                'verified_at' => now(),
                'failure_reason' => null,
            ]);
            $locked->order()->update(['payment_status' => 'paid']);
        });
    }
}
