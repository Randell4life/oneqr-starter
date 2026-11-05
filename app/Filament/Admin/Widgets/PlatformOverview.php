<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\BusinessSubscriptionPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $monthRevenue = BusinessSubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $activeSubscriptions = BusinessSubscription::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();

        $attentionPayments = BusinessSubscriptionPayment::query()
            ->whereIn('status', ['pending', 'initiated', 'review'])
            ->count();

        return [
            Stat::make('ბიზნესები', number_format(Business::count()))
                ->description('რეგისტრირებული ბიზნესები')
                ->color('primary'),
            Stat::make('მომხმარებლები', number_format(User::count()))
                ->description('რეგისტრირებული ანგარიშები'),
            Stat::make('აქტიური გამოწერები', number_format($activeSubscriptions))
                ->description('მოქმედი გამოწერები')
                ->color('success'),
            Stat::make('ამ თვის შემოსავალი', number_format((float) $monthRevenue, 2).' GEL')
                ->description('მხოლოდ დადასტურებული გადახდები')
                ->color('success'),
            Stat::make('გადასამოწმებელი გადახდები', number_format($attentionPayments))
                ->description('მოლოდინი ან ხელით შემოწმება')
                ->color($attentionPayments > 0 ? 'warning' : 'success'),
        ];
    }
}
