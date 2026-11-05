<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessRegistrationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'business_name' => ['required', 'string', 'max:150'],
            'business_type' => ['required', Rule::in(['restaurant', 'retail', 'hotel', 'services', 'beauty', 'events', 'other'])],
        ]);

        $user = DB::transaction(function () use ($validated): \App\Models\User {
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $baseSlug = Str::slug($validated['business_name']) ?: 'business';
            $slug = $baseSlug;
            $suffix = 2;

            while (Business::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $user->businesses()->create([
                'name' => $validated['business_name'],
                'slug' => $slug,
                'business_type' => $validated['business_type'],
                'status' => 'pending',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
