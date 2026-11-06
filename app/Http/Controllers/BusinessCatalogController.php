<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessCatalogItem;
use App\Models\BusinessOrderRequest;
use App\Support\BusinessAccess;
use App\Support\PlanAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessCatalogController extends Controller
{
    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewQr($request->user(), $business);

        return view('business.catalog-manage', [
            'business' => $business,
            'items' => $business->catalogItems()->orderBy('sort_order')->orderBy('name')->paginate(20),
            'orders' => $business->orderRequests()->with('catalogItem')->latest()->paginate(20, ['*'], 'orders_page'),
            'inquiries' => $business->inquiries()->latest()->paginate(20, ['*'], 'inquiries_page'),
            'canManage' => $access->canManageQr($request->user(), $business),
        ]);
    }

    public function store(Request $request, Business $business, BusinessAccess $access, PlanAccess $plans): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $plans->assertCanCreate($business, 'catalog_items');

        $data = $this->validated($request, $business);
        $data['slug'] = $this->uniqueSlug($business, $data['name']);
        $data['image_path'] = $request->file('image')?->store('business-catalog', 'public');
        unset($data['image']);

        $business->catalogItems()->create($data);

        return back()->with('status', 'კატალოგის ჩანაწერი დაემატა.');
    }

    public function update(Request $request, Business $business, int $item, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $business->catalogItems()->findOrFail($item);
        $data = $this->validated($request, $business, $item);

        if ($request->hasFile('image')) {
            if ($record->image_path) {
                Storage::disk('public')->delete($record->image_path);
            }
            $data['image_path'] = $request->file('image')->store('business-catalog', 'public');
        }
        unset($data['image']);
        $record->update($data);

        return back()->with('status', 'კატალოგის ჩანაწერი განახლდა.');
    }

    public function destroy(Request $request, Business $business, int $item, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $business->catalogItems()->findOrFail($item);
        if ($record->image_path) {
            Storage::disk('public')->delete($record->image_path);
        }
        $record->delete();

        return back()->with('status', 'კატალოგის ჩანაწერი წაიშალა.');
    }

    public function submitOrder(Request $request, string $slug, int $item): RedirectResponse
    {
        $business = Business::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $catalogItem = $business->catalogItems()->where('is_available', true)->findOrFail($item);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'message' => ['nullable', 'string', 'max:1500'],
        ]);

        BusinessOrderRequest::create($data + [
            'business_id' => $business->id,
            'catalog_item_id' => $catalogItem->id,
            'item_name' => $catalogItem->name,
            'unit_price' => $catalogItem->price,
            'currency' => $catalogItem->currency,
            'status' => 'new',
        ]);

        return back()->with('order_status', 'მოთხოვნა წარმატებით გაიგზავნა. ბიზნესი დაგიკავშირდებათ.');
    }

    public function updateOrderStatus(Request $request, Business $business, int $order, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);
        $record = $business->orderRequests()->findOrFail($order);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'processing', 'completed', 'cancelled'])],
        ]);
        $record->update($data);

        return back()->with('status', 'შეკვეთის მოთხოვნის სტატუსი განახლდა.');
    }

    private function validated(Request $request, Business $business, ?int $item = null): array
    {
        $existing = $item ? $business->catalogItems()->findOrFail($item) : null;
        $data = $request->validate([
            'type' => ['required', Rule::in(['product', 'service'])],
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('business_catalog_items', 'sku')->where('business_id', $business->id)->ignore($item)],
            'description' => ['nullable', 'string', 'max:4000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(['GEL', 'USD', 'EUR'])],
            'is_available' => ['nullable', 'boolean'],
            'track_inventory' => ['nullable', 'boolean'],
            'stock_quantity' => $existing?->track_inventory
                ? ['nullable', 'integer', 'min:0', 'max:100000000']
                : ['nullable', 'required_if:track_inventory,1', 'integer', 'min:0', 'max:100000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($existing?->track_inventory && $data['type'] !== 'product') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'მარაგის აღრიცხვაში მყოფი პროდუქტის სერვისად გადაკეთება შეუძლებელია.',
            ]);
        }

        $trackInventory = $existing?->track_inventory
            ? true
            : ($data['type'] === 'product' && $request->boolean('track_inventory'));
        $data['track_inventory'] = $trackInventory;
        $data['stock_quantity'] = $existing?->track_inventory
            ? (int) $existing->stock_quantity
            : ($trackInventory ? (int) $request->input('stock_quantity', 0) : null);
        $data['low_stock_threshold'] = (int) $request->input('low_stock_threshold', $existing?->low_stock_threshold ?? 5);
        $data['sku'] = filled($data['sku'] ?? null) ? trim($data['sku']) : null;
        $data['is_available'] = $request->boolean('is_available');
        $data['sort_order'] = (int) $request->input('sort_order', 0);
        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(Business $business, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($business->catalogItems()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
