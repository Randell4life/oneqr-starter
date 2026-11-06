<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartPageInteractionController extends Controller
{
    private const TYPES = [
        'call', 'email', 'website', 'directions', 'cta', 'catalog', 'cart', 'order', 'social',
    ];

    public function store(Request $request, string $slug): JsonResponse
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
        ]);

        $business->smartPageInteractions()->create([
            'interaction_type' => $validated['type'],
            'occurred_at' => now(),
        ]);

        return response()->json(['recorded' => true], 202);
    }
}
