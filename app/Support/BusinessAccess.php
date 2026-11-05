<?php

namespace App\\Support;

use App\\Models\\Business;
use App\\Models\\BusinessMembership;
use App\\Models\\User;
use Illuminate\\Database\\Eloquent\\Builder;

class BusinessAccess
{
    public function membership(User $user, Business $business): ?BusinessMembership
    {
        return $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    public function isOwner(User $user, Business $business): bool
    {
        return (int) $business->owner_id === (int) $user->id;
    }

    public function canViewQr(User $user, Business $business): bool
    {
        return $this->isOwner($user, $business) || $this->membership($user, $business) !== null;
    }

    public function canManageQr(User $user, Business $business): bool
    {
        if ($this->isOwner($user, $business)) {
            return true;
        }

        return $this->membership($user, $business)?->role === 'manager';
    }

    public function canViewAnalytics(User $user, Business $business): bool
    {
        return $this->canManageQr($user, $business);
    }

    public function visibleQrCodes(User $user, Business $business): Builder
    {
        $query = $business->qrCodes();

        if (! $this->isOwner($user, $business)) {
            $membership = $this->membership($user, $business);

            if ($membership?->branch_id) {
                $query->where(function (Builder $branchQuery) use ($membership): void {
                    $branchQuery->whereNull('branch_id')
                        ->orWhere('branch_id', $membership->branch_id);
                });
            }
        }

        return $query;
    }

    public function branchForNewQr(User $user, Business $business): ?int
    {
        if ($this->isOwner($user, $business)) {
            return null;
        }

        return $this->membership($user, $business)?->branch_id;
    }

    public function assertCanViewQr(User $user, Business $business): void
    {
        abort_unless($this->canViewQr($user, $business), 403);
    }

    public function assertCanManageQr(User $user, Business $business): void
    {
        abort_unless($this->canManageQr($user, $business), 403);
    }

    public function assertCanViewAnalytics(User $user, Business $business): void
    {
        abort_unless($this->canViewAnalytics($user, $business), 403);
    }
}
