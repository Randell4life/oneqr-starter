# TBC E-Commerce — oneQR

## Prerequisites
1. Register oneQR as a merchant application in the TBC developer portal.
2. Request activation of TBC E-Commerce for the merchant.
3. Obtain the developer API key, merchant Client ID and Client Secret.
4. Configure the callback URL in the TBC E-Commerce merchant dashboard.

## Environment
Set these values in the production `.env` file only. Never commit real credentials:
```dotenv
TBC_ENABLED=true
TBC_API_KEY=your-developer-api-key
TBC_CLIENT_ID=your-merchant-client-id
TBC_CLIENT_SECRET=your-merchant-client-secret
```

Then refresh Laravel's configuration cache:
```bash
php artisan config:clear
php artisan config:cache
```

## Callback URL
The callback endpoint is:
```
https://YOUR-DOMAIN/payments/tbc/callback
```
Register this exact HTTPS URL in the TBC E-Commerce merchant dashboard. TBC's documentation lists these callback source IP addresses for network allowlisting:
- `193.104.20.44`
- `193.104.20.45`
- `185.52.80.44`
- `185.52.80.45`

The callback only supplies a payment ID. oneQR does not trust the callback as proof of payment: it requests payment details from TBC and verifies the payment ID, final status, amount, currency and merchant reference before activating a subscription or marking an order paid.

## Customer order payments
The public cart checkout offers TBC card payment only when TBC is enabled and the order currency is GEL. The customer is redirected to TBC's hosted checkout page. The same callback endpoint dispatches the payment to either the subscription or order payment record. An order is marked paid only after the server verifies `payId`, `merchantPaymentId`, successful final status, exact amount and GEL currency. A failed or mismatched payment never marks the order as paid. Customers return to the public business page after checkout; the order remains available to the business even if payment fails.

Card payments are one-time payments. This integration does not store card data and does not enable recurring charges.

## Plan pricing
The Starter, Pro and Business plan prices are intentionally unset. Set the approved GEL monthly price in `subscription_plans.monthly_price` only after commercial approval. The checkout endpoint rejects plans without a positive GEL price.

## Billing behavior
Each verified successful checkout activates the selected plan for one month. Automatic recurring billing is not enabled. The customer must initiate a new payment for the next period. The Free plan remains the fallback after the paid period expires.

## Local verification
```bash
php artisan migrate
php artisan test
```

Do not enable `TBC_ENABLED` on a public environment until the merchant credentials and callback URL have been configured and tested with TBC.