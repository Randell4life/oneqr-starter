<?php

namespace App\Support;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BusinessAccess
{
    public function membership(User $user, Business $business): ?BusinessMembership
    {
        return $business->memberships()->where('user_id', $user->id)->where('status', 'active')->first();
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
        return $this->isOwner($user, $business) || $this->membership($user, $business)?->role === 'manager';
    }

    public function canViewAnalytics(User $user, Business $business): bool
    {
        return $this->canManageQr($user, $business);
    }

    public function visibleQrCodes(User $user, Business $business): HasMany
    {
        $query = $business->qrCodes();
        $membership = $this->isOwner($user, $business) ? null : $this->membership($user, $business);

        if ($membership?->branch_id) {
            $query->where(function ($scope) use ($membership): void {
                $scope->whereNull('branch_id')->orWhere('branch_id', $membership->branch_id);
            });
        }

        return $query;
    }

    public function canAccessQr(User $user, Business $business, QrCode $qrCode): bool
    {
        if ((int) $qrCode->business_id !== (int) $business->id || ! $this->canViewQr($user, $business)) {
            return false;
        }

        if ($this->isOwner($user, $business)) {
            return true;
        }

        $membership = $this->membership($user, $business);

        return $membership !== null && (
            $membership->branch_id === null ||
            $qrCode->branch_id === null ||
            (int) $membership->branch_id === (int) $qrCode->branch_id
        );
    }

    public function assertCanViewQr(User $user, Business $business): void
    {
        abort_unless($this->canViewQr($user, $business), 403);
    }

    public function assertCanManageQr(User $user, Business $business): void
    {
        abort_unless($this->canManageQr($user, $business), 403);
    }

    public function assertCanAccessQr(User $user, Business $business, QrCode $qrCode): void
    {
        abort_unless($this->canAccessQr($user, $business, $qrCode), 403);
    }

    public function assertCanViewAnalytics(User $user, Business $business): void
    {
        abort_unless($this->canViewAnalytics($user, $business), 403);
    }
}
