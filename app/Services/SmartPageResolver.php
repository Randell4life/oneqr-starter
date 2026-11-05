<?php

namespace App\Services;

use App\Models\Business;
use App\Models\SmartPage;
use Carbon\CarbonImmutable;

class SmartPageResolver
{
    public function resolve(Business $business): ?SmartPage
    {
        $now = CarbonImmutable::now($business->timezone ?: config('app.timezone'));
        $schedules = $business->smartPageSchedules()
            ->with(['page.blocks'])
            ->where('is_active', true)
            ->whereHas('page', fn ($query) => $query->where('is_published', true))
            ->orderByDesc('is_override')
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        foreach ($schedules as $schedule) {
            if ($this->matches($schedule, $now)) {
                return $schedule->page;
            }
        }

        return $business->smartPages()
            ->with('blocks')
            ->where('is_default', true)
            ->where('is_published', true)
            ->first();
    }

    private function matches($schedule, CarbonImmutable $now): bool
    {
        if ($schedule->starts_on && $now->toDateString() < $schedule->starts_on->toDateString()) {
            return false;
        }

        if ($schedule->ends_on && $now->toDateString() > $schedule->ends_on->toDateString()) {
            return false;
        }

        if (is_array($schedule->weekdays) && $schedule->weekdays !== [] && ! in_array($now->dayOfWeek, $schedule->weekdays, true)) {
            return false;
        }

        if ($schedule->starts_at && $schedule->ends_at) {
            $time = $now->format('H:i:s');
            $start = (string) $schedule->starts_at;
            $end = (string) $schedule->ends_at;
            $inside = $start <= $end
                ? ($time >= $start && $time < $end)
                : ($time >= $start || $time < $end);

            if (! $inside) {
                return false;
            }
        }

        return true;
    }
}
