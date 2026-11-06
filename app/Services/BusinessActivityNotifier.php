<?php

namespace App\Services;

use App\Models\Business;
use App\Notifications\BusinessActivityNotification;
use Illuminate\Support\Facades\Notification;

class BusinessActivityNotifier
{
    public function notify(
        Business $business,
        string $event,
        string $title,
        string $message,
        string $url,
    ): void {
        $recipients = collect([$business->owner])
            ->merge(
                $business->memberships()
                    ->where('status', 'active')
                    ->with('user')
                    ->get()
                    ->pluck('user')
            )
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new BusinessActivityNotification(
                event: $event,
                title: $title,
                message: $message,
                url: $url,
                businessId: (int) $business->id,
                businessName: $business->name,
            ),
        );
    }
}
