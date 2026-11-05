<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BusinessProfileController extends Controller
{
    public function edit(Request $request, Business $business): View
    {
        $this->authorizeOwner($request, $business);

        return view('business.profile-edit', compact('business'));
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeOwner($request, $business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'business_type' => ['required', 'in:restaurant,retail,hotel,services,beauty,events,other'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        foreach (['logo' => 'logo_path', 'cover' => 'cover_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($business->{$column}) {
                    Storage::disk('public')->delete($business->{$column});
                }

                $data[$column] = $request->file($input)->store('business-profiles', 'public');
            }
        }

        unset($data['logo'], $data['cover']);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published']
            ? ($business->published_at ?? now())
            : null;

        $business->update($data);

        return back()->with('status', 'ბიზნესის პროფილი წარმატებით განახლდა.');
    }

    public function show(string $slug): View
    {
        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['catalogItems' => fn ($query) => $query->where('is_available', true)->orderBy('sort_order')->orderBy('name')])
            ->firstOrFail();

        return view('business.public-profile', compact('business'));
    }

    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) $request->user()->id, 403);
    }
}
