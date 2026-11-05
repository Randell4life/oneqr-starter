<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TbcCheckout
{
    private const BASE_URL = 'https://api.tbcbank.ge/v1/tpay';

    private function config(string $key): string
    {
        $value = (string) config('tbc.'.$key, '');

        if ($value === '') {
            throw new RuntimeException('TBC E-Commerce credentials are not configured.');
        }

        return $value;
    }

    private function accessToken(): string
    {
        return Cache::remember('oneqr:tbc:access-token', now()->addHours(23), function (): string {
            $response = Http::asForm()
                ->acceptJson()
                ->withHeaders(['apikey' => $this->config('api_key')])
                ->timeout(20)
                ->post(self::BASE_URL.'/access-token', [
                    'client_id' => $this->config('client_id'),
                    'client_secret' => $this->config('client_secret'),
                ]);

            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('TBC access token could not be obtained.');
            }

            return $response->json('access_token');
        });
    }

    private function request()
    {
        return Http::acceptJson()
            ->withHeaders([
                'apikey' => $this->config('api_key'),
                'Authorization' => 'Bearer '.$this->accessToken(),
            ])
            ->timeout(25);
    }

    public function createPayment(
        string $merchantPaymentId,
        float $amount,
        string $returnUrl,
        string $callbackUrl,
        ?string $userIp = null
    ): array {
        $payload = [
            'amount' => [
                'currency' => 'GEL',
                'total' => number_format($amount, 2, '.', ''),
            ],
            'returnurl' => $returnUrl,
            'callbackUrl' => $callbackUrl,
            'merchantPaymentId' => $merchantPaymentId,
            'description' => 'oneQR subscription',
            'expirationMinutes' => 12,
            'preAuth' => false,
            'language' => 'KA',
        ];

        if ($userIp !== null) {
            $payload['userIpAddress'] = $userIp;
        }

        $response = $this->request()->post(self::BASE_URL.'/payments', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('TBC payment initialization failed.');
        }

        $data = $response->json();
        $payId = $data['payId'] ?? null;
        $approvalUrl = collect($data['links'] ?? [])
            ->firstWhere('rel', 'approval_url')['uri'] ?? null;

        if (! is_string($payId) || ! is_string($approvalUrl) || ! str_starts_with($approvalUrl, 'https://')) {
            throw new RuntimeException('TBC did not return a valid checkout URL.');
        }

        return [
            'pay_id' => $payId,
            'checkout_url' => $approvalUrl,
        ];
    }

    public function paymentDetails(string $payId): array
    {
        $response = $this->request()->get(self::BASE_URL.'/payments/'.rawurlencode($payId));

        if (! $response->successful()) {
            throw new RuntimeException('TBC payment status could not be verified.');
        }

        return $response->json();
    }
}
