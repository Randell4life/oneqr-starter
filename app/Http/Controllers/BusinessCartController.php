<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessCatalogItem;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderPayment;
use App\Services\Payments\TbcCheckout;
use App\Support\BusinessAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessCartController extends Controller
{
    public function show(string $slug): View
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $cart = session()->get($this->cartKey($business), []);
        $items = $business->catalogItems()
            ->where('is_available', true)
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $lines = collect($cart)->map(function ($quantity, $id) use ($items) {
            $item = $items->get($id);

            return $item ? ['item' => $item, 'quantity' => (int) $quantity, 'line_total' => round((float) $item->price * (int) $quantity, 2)] : null;
        })->filter()->values();

        return view('business.cart', [
            'business' => $business,
            'lines' => $lines,
            'subtotal' => round($lines->sum('line_total'), 2),
            'currency' => $lines->first()['item']->currency ?? 'GEL',
        ]);
    }

    public function add(Request $request, string $slug, int $item): RedirectResponse
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $catalogItem = $business->catalogItems()->where('is_available', true)->findOrFail($item);
        if ($catalogItem->price === null) {
            return back()->withErrors(['cart' => 'ამ სერვისის ფასი შეთანხმებითაა და კალათაში ვერ დაემატება.']);
        }

        $cart = session()->get($this->cartKey($business), []);
        $existingCurrencies = $business->catalogItems()->whereIn('id', array_keys($cart))->pluck('currency')->unique();
        if ($existingCurrencies->isNotEmpty() && ($existingCurrencies->count() > 1 || $existingCurrencies->first() !== $catalogItem->currency)) {
            return back()->withErrors(['cart' => 'ერთ კალათაში ერთი ვალუტის პროდუქტების გაერთიანებაა შესაძლებელი.']);
        }
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $quantity = (int) ($data['quantity'] ?? 1);
        $cart = session()->get($this->cartKey($business), []);
        $cart[$catalogItem->id] = min(100, (int) ($cart[$catalogItem->id] ?? 0) + $quantity);
        session()->put($this->cartKey($business), $cart);

        return back()->with('cart_status', 'პროდუქტი კალათაში დაემატა.');
    }

    public function update(Request $request, string $slug, int $item): RedirectResponse
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $cart = session()->get($this->cartKey($business), []);
        abort_unless(array_key_exists($item, $cart), 404);
        $cart[$item] = (int) $data['quantity'];
        session()->put($this->cartKey($business), $cart);

        return back()->with('cart_status', 'კალათა განახლდა.');
    }

    public function remove(string $slug, int $item): RedirectResponse
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $cart = session()->get($this->cartKey($business), []);
        unset($cart[$item]);
        session()->put($this->cartKey($business), $cart);

        return back()->with('cart_status', 'პროდუქტი კალათიდან წაიშალა.');
    }

    public function checkout(Request $request, string $slug, TbcCheckout $tbc): RedirectResponse
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer', 'tbc_card'])],
        ]);

        if ($data['payment_method'] === 'tbc_card' && (! config('tbc.enabled') || ! config('tbc.api_key'))) {
            return back()->withInput()->withErrors(['payment_method' => 'ბარათით გადახდა ამჟამად მიუწვდომელია.']);
        }

        $cart = session()->get($this->cartKey($business), []);
        if ($cart === []) {
            return back()->withErrors(['cart' => 'კალათა ცარიელია.']);
        }

        $order = DB::transaction(function () use ($business, $cart, $data) {
            $items = $business->catalogItems()
                ->where('is_available', true)
                ->whereIn('id', array_keys($cart))
                ->lockForUpdate()
                ->get();

            if ($items->count() !== count($cart)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cart' => 'კალათაში არის პროდუქტი, რომელიც აღარ არის ხელმისაწვდომი. განაახლე კალათა.',
                ]);
            }

            $currencies = $items->pluck('currency')->unique();
            if ($data['payment_method'] === 'tbc_card' && $currencies->first() !== 'GEL') {
                throw \\Illuminate\\Validation\\ValidationException::withMessages([
                    'payment_method' => 'TBC ბარათით გადახდა ამ ეტაპზე მხოლოდ GEL ვალუტაშია შესაძლებელი.',
                ]);
            }
            if ($currencies->count() !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cart' => 'ერთ შეკვეთაში სხვადასხვა ვალუტის პროდუქტების გაერთიანება შეუძლებელია.',
                ]);
            }

            foreach ($items as $item) {
                if ($item->price === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => $item->name.' — ფასი შეთანხმებითაა. დაუკავშირდი ბიზნესს ცალკე.',
                    ]);
                }
            }

            $subtotal = round($items->sum(fn ($item) => (float) $item->price * (int) $cart[$item->id]), 2);
            $order = BusinessOrder::create($data + [
                'business_id' => $business->id,
                'order_number' => 'OQR-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => $currencies->first(),
                'status' => 'new',
                'payment_status' => 'unpaid',
            ]);

            foreach ($items as $item) {
                $quantity = (int) $cart[$item->id];
                $unitPrice = round((float) $item->price, 2);
                $order->items()->create([
                    'catalog_item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => round($unitPrice * $quantity, 2),
                ]);
            }

            return $order;
        });

        session()->forget($this->cartKey($business));

        if ($order->payment_method === 'tbc_card') {
            $payment = BusinessOrderPayment::create([
                'uuid' => (string) Str::uuid(),
                'business_order_id' => $order->id,
                'provider' => 'tbc',
                'status' => 'pending',
                'amount' => $order->total,
                'currency' => $order->currency,
                'merchant_payment_id' => 'OQRORD'.Str::upper(Str::random(20)),
                'expires_at' => now()->addMinutes(12),
            ]);

            try {
                $checkout = $tbc->createPayment(
                    $payment->merchant_payment_id,
                    (float) $payment->amount,
                    route('business.orders.payment.return', $payment->uuid, true),
                    route('payments.tbc.callback', [], true),
                    $request->ip(),
                    'oneQR order '.$order->order_number
                );
                $payment->update([
                    'provider_payment_id' => $checkout['pay_id'],
                    'checkout_url' => $checkout['checkout_url'],
                    'status' => 'initiated',
                ]);
            } catch (\RuntimeException $exception) {
                report($exception);
                $payment->update(['status' => 'failed', 'failure_reason' => 'TBC payment initialization failed.']);
                $order->update(['payment_status' => 'failed']);

                return redirect()->route('business.public', $business->slug)
                    ->with('payment_error', 'ბანკთან დაკავშირება ვერ მოხერხდა. შეკვეთა შენარჩუნებულია, სცადე მოგვიანებით ან დაუკავშირდი ბიზნესს.');
            }

            return redirect()->away($payment->checkout_url);
        }

        return redirect()->route('business.public', $business->slug)
            ->with('order_success', ['number' => $order->order_number, 'total' => number_format((float) $order->total, 2).' '.$order->currency]);
    }

    public function orders(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewQr($request->user(), $business);

        return view('business.orders', [
            'business' => $business,
            'orders' => $business->orders()->with('items')->latest()->paginate(25),
            'canManage' => $access->canManageQr($request->user(), $business),
        ]);
    }

    public function updateStatus(Request $request, Business $business, int $order, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $business->orders()->findOrFail($order);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'confirmed', 'processing', 'completed', 'cancelled'])],
        ]);
        $record->update($data);

        return back()->with('status', 'შეკვეთის სტატუსი განახლდა.');
    }

    private function cartKey(Business $business): string
    {
        return 'oneqr_cart_'.$business->id;
    }
}
