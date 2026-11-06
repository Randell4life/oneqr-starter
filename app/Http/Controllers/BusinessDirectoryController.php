<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'in:restaurant,retail,hotel,services,beauty,events,other'],
            'city' => ['nullable', 'string', 'max:100'],
            'nearby' => ['nullable', 'in:1'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'sort' => ['nullable', 'in:recent,rated,name'],
        ]);

        $query = Business::query()
            ->where('is_published', true)
            ->where('status', 'active')
            ->withCount(['catalogItems as available_items_count' => fn ($items) => $items->where('is_available', true)])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($reviews) => $reviews->where('status', 'approved')], 'rating')
            ->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->where('status', 'approved')]);

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($businesses) use ($term): void {
                $businesses->where('name', 'like', "%{$term}%")
                    ->orWhere('tagline', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhereHas('catalogItems', fn ($items) => $items
                        ->where('is_available', true)
                        ->where(function ($catalog) use ($term): void {
                            $catalog->where('name', 'like', "%{$term}%")
                                ->orWhere('description', 'like', "%{$term}%");
                        }));
            });
        }

        if (!empty($filters['category'])) {
            $query->where('business_type', $filters['category']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['nearby']) && isset($filters['lat'], $filters['lng'])) {
            $latitude = (float) $filters['lat'];
            $longitude = (float) $filters['lng'];
            $latitudeDelta = 25 / 111;
            $longitudeDelta = 25 / (111 * max(abs(cos(deg2rad($latitude))), 0.01));
            $query->whereBetween('latitude', [max(-90, $latitude - $latitudeDelta), min(90, $latitude + $latitudeDelta)])
                ->whereBetween('longitude', [max(-180, $longitude - $longitudeDelta), min(180, $longitude + $longitudeDelta)]);
        }

        match ($filters['sort'] ?? 'recent') {
            'rated' => $query->orderByDesc('approved_reviews_avg_rating')->orderByDesc('approved_reviews_count'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $businesses = $query->paginate(12)->withQueryString();

        $mapBusinesses = Business::query()
            ->where('is_published', true)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when(!empty($filters['category']), fn ($q) => $q->where('business_type', $filters['category']))
            ->when(!empty($filters['city']), fn ($q) => $q->where('city', $filters['city']))
            ->when(!empty($filters['q']), function ($q) use ($filters): void {
                $term = trim($filters['q']);
                $q->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('tagline', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%")
                        ->orWhere('address', 'like', "%{$term}%")
                        ->orWhereHas('catalogItems', fn ($items) => $items->where('is_available', true)->where('name', 'like', "%{$term}%"));
                });
            })
            ->when(!empty($filters['nearby']) && isset($filters['lat'], $filters['lng']), function ($q) use ($filters): void {
                $latitude = (float) $filters['lat'];
                $longitude = (float) $filters['lng'];
                $latitudeDelta = 25 / 111;
                $longitudeDelta = 25 / (111 * max(abs(cos(deg2rad($latitude))), 0.01));
                $q->whereBetween('latitude', [max(-90, $latitude - $latitudeDelta), min(90, $latitude + $latitudeDelta)])
                    ->whereBetween('longitude', [max(-180, $longitude - $longitudeDelta), min(180, $longitude + $longitudeDelta)]);
            })
            ->select(['id', 'name', 'slug', 'business_type', 'city', 'address', 'latitude', 'longitude'])
            ->limit(250)
            ->get();

        $hasFilters = collect($filters)->contains(fn ($value) => filled($value));
        $popularBusinesses = collect();
        if (!$hasFilters) {
            $popularBusinesses = Business::query()
                ->where('is_published', true)
                ->where('status', 'active')
                ->whereHas('favoritedBy')
                ->withCount('favoritedBy as favorites_count')
                ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($reviews) => $reviews->where('status', 'approved')], 'rating')
                ->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->where('status', 'approved')])
                ->orderByDesc('favorites_count')
                ->orderByDesc('approved_reviews_avg_rating')
                ->limit(4)
                ->get();
        }

        $categoryCounts = Business::query()
            ->where('is_published', true)
            ->where('status', 'active')
            ->selectRaw('business_type, COUNT(*) as businesses_count')
            ->groupBy('business_type')
            ->pluck('businesses_count', 'business_type');

        $cities = Business::query()
            ->where('is_published', true)
            ->where('status', 'active')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $categoryLabels = [
            'restaurant' => 'რესტორნები და კაფეები',
            'retail' => 'მაღაზიები',
            'hotel' => 'სასტუმროები',
            'services' => 'სერვისები',
            'beauty' => 'სილამაზე და მოვლა',
            'events' => 'ღონისძიებები',
            'other' => 'სხვა ბიზნესი',
        ];

        return view('business.directory', compact('businesses', 'cities', 'categoryLabels', 'categoryCounts', 'popularBusinesses', 'mapBusinesses', 'hasFilters', 'filters'));
    }
}
