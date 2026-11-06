<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSubscriptionPlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePlatformAdmin($request);

        $plans = SubscriptionPlan::query()
            ->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query
                ->whereIn('status', ['active', 'trialing'])
                ->where(fn ($active) => $active->whereNull('ends_at')->orWhere('ends_at', '>', now()))])
            ->orderBy('sort_order')
            ->get();

        return view('platform.subscription-plans', compact('plans'));
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'in:GEL'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'limits' => ['required', 'array'],
            'limits.qr_codes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'limits.branches' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'limits.team_members' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'limits.catalog_items' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'features_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $limits = [];
        foreach (['qr_codes', 'branches', 'team_members', 'catalog_items'] as $feature) {
            $value = $data['limits'][$feature] ?? null;
            $limits[$feature] = $value === '' || $value === null ? null : (int) $value;
        }

        $features = collect(preg_split('/\r\n|\r|\n/', $data['features_text'] ?? ''))
            ->map(fn (string $feature) => trim($feature))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'monthly_price' => $data['monthly_price'] ?? null,
            'currency' => $data['currency'],
            'limits' => $limits,
            'features' => $features,
            'is_active' => $plan->slug === 'free' ? true : $request->boolean('is_active'),
            'sort_order' => $data['sort_order'],
        ]);

        return back()->with('status', 'გამოწერის გეგმა წარმატებით განახლდა.');
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);
    }
}
