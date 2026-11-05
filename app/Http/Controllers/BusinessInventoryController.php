<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessInventoryMovement;
use App\Support\BusinessAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessInventoryController extends Controller
{
    public function index(Request $request, Business $business, BusinessAccess $access): View
    {
        $access->assertCanViewQr($request->user(), $business);

        $items = $business->catalogItems()
            ->where('type', 'product')
            ->orderByDesc('track_inventory')
            ->orderBy('name')
            ->paginate(30);

        $tracked = $business->catalogItems()->where('type', 'product')->where('track_inventory', true);
        $summary = [
            'tracked' => (clone $tracked)->count(),
            'low' => (clone $tracked)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'units' => (clone $tracked)->sum('stock_quantity'),
            'out' => (clone $tracked)->where('stock_quantity', 0)->count(),
        ];

        return view('business.inventory', [
            'business' => $business,
            'items' => $items,
            'summary' => $summary,
            'canManage' => $access->canManageQr($request->user(), $business),
            'movements' => $business->inventoryMovements()->with(['catalogItem', 'user', 'order'])->latest()->limit(12)->get(),
        ]);
    }

    public function adjust(Request $request, Business $business, int $item, BusinessAccess $access): RedirectResponse
    {
        $access->assertCanManageQr($request->user(), $business);

        $data = $request->validate([
            'movement' => ['required', 'in:restock,adjustment'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $business, $item, $data): void {
            $record = $business->catalogItems()->where('type', 'product')->lockForUpdate()->findOrFail($item);

            if (! $record->track_inventory) {
                abort(422, 'ამ პროდუქტზე მარაგის აღრიცხვა ჩართული არ არის.');
            }

            $before = (int) $record->stock_quantity;
            $delta = $data['movement'] === 'restock' ? (int) $data['quantity'] : -(int) $data['quantity'];
            $after = $before + $delta;

            if ($after < 0) {
                abort(422, 'მარაგში საკმარისი რაოდენობა არ არის.');
            }

            $record->update(['stock_quantity' => $after]);

            BusinessInventoryMovement::create([
                'business_id' => $business->id,
                'catalog_item_id' => $record->id,
                'user_id' => $request->user()->id,
                'type' => $data['movement'],
                'quantity' => $delta,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $data['note'] ?? null,
            ]);
        });

        return back()->with('status', 'მარაგი წარმატებით განახლდა.');
    }
}
