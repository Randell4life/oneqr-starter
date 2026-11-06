<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessFavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $businesses = $request->user()
            ->favoriteBusinesses()
            ->where('businesses.is_published', true)
            ->withCount('qrCodes')
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('business.favorites.index', compact('businesses'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $request->user()->favoriteBusinesses()->syncWithoutDetaching([$business->id]);

        return back()->with('favorite_status', 'ბიზნესი რჩეულებში შეინახა.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $request->user()->favoriteBusinesses()->detach($business->id);

        return back()->with('favorite_status', 'ბიზნესი რჩეულებიდან წაიშალა.');
    }
}
