<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessManagementController extends Controller
{
    public function index(Request $request, Business $business): View
    {
        $this->authorizeOwner($request, $business);

        return view('business.management', [
            'business' => $business,
            'branches' => $business->branches()->withCount('memberships')->orderByDesc('is_primary')->orderBy('name')->get(),
            'memberships' => $business->memberships()->with(['user', 'branch'])->latest()->get(),
        ]);
    }

    public function storeBranch(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeOwner($request, $business);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'timezone' => ['required', 'timezone'],
        ]);

        $base = Str::slug($data['name']) ?: 'branch';
        $slug = $base;
        $suffix = 2;
        while ($business->branches()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $isFirst = ! $business->branches()->exists();
        $business->branches()->create($data + [
            'slug' => $slug,
            'is_primary' => $isFirst,
            'is_active' => true,
        ]);

        return back()->with('status', 'ფილიალი წარმატებით დაემატა.');
    }

    public function destroyBranch(Request $request, Business $business, int $branch): RedirectResponse
    {
        $this->authorizeOwner($request, $business);
        $record = $business->branches()->findOrFail($branch);

        abort_if($record->is_primary, 422, 'ძირითადი ფილიალის წაშლა შეუძლებელია.');

        $record->delete();

        return back()->with('status', 'ფილიალი წაიშალა.');
    }

    public function inviteMember(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeOwner($request, $business);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190', 'exists:users,email'],
            'role' => ['required', Rule::in(['manager', 'staff'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('business_branches', 'id')->where('business_id', $business->id)],
        ]);

        $member = \App\Models\User::where('email', $data['email'])->firstOrFail();
        abort_if($member->id === $business->owner_id, 422, 'ბიზნესის მფლობელს თანამშრომლად ვერ დაამატებთ.');

        $existing = $business->memberships()->where('user_id', $member->id)->first();
        abort_if($existing, 422, 'ეს მომხმარებელი უკვე მოწვეულია ან გუნდის წევრია.');

        $business->memberships()->create([
            'user_id' => $member->id,
            'branch_id' => $data['branch_id'] ?? null,
            'invited_by' => $request->user()->id,
            'role' => $data['role'],
            'status' => 'invited',
        ]);

        return back()->with('status', 'მოწვევა შეიქმნა. თანამშრომელმა ის საკუთარ კაბინეტში უნდა დაადასტუროს.');
    }

    public function destroyMember(Request $request, Business $business, int $membership): RedirectResponse
    {
        $this->authorizeOwner($request, $business);
        $business->memberships()->findOrFail($membership)->delete();

        return back()->with('status', 'გუნდის წევრი/მოწვევა წაიშალა.');
    }

    public function invitations(Request $request): View
    {
        return view('business.invitations', [
            'invitations' => $request->user()->memberships()
                ->with(['business', 'branch', 'inviter'])
                ->where('status', 'invited')
                ->latest()
                ->get(),
        ]);
    }

    public function acceptInvitation(Request $request, int $membership): RedirectResponse
    {
        $invite = $request->user()->memberships()
            ->whereKey($membership)
            ->where('status', 'invited')
            ->firstOrFail();

        $invite->update(['status' => 'active', 'accepted_at' => now()]);

        return redirect()->route('team.invitations')->with('status', 'მოწვევა მიღებულია.');
    }

    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) $request->user()->id, 403);
    }
}
