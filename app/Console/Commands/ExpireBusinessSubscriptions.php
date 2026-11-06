<?php

namespace App\Console\Commands;

use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBusinessSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire ended business subscriptions and return businesses to the Free plan.';

    public function handle(): int
    {
        $now = now();
        $freePlan = SubscriptionPlan::query()->where('slug', 'free')->first();

        if (! $freePlan) {
            $this->error('The Free subscription plan is missing.');
            return self::FAILURE;
        }

        $expired = 0;

        BusinessSubscription::query()
            ->whereIn('status', ['active', 'trialing', 'canceled'])
            ->where(function ($query) use ($now): void {
                $query->where(function ($ends) use ($now): void {
                    $ends->whereNotNull('ends_at')->where('ends_at', '<=', $now);
                })->orWhere(function ($trial) use ($now): void {
                    $trial->where('status', 'trialing')
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', $now);
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($freePlan, &$expired): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, $freePlan, &$expired): void {
                        $locked = BusinessSubscription::query()
                            ->whereKey($subscription->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked || ! in_array($locked->status, ['active', 'trialing', 'canceled'], true)) {
                            return;
                        }

                        $now = now();
                        $hasEnded = $locked->ends_at !== null && $locked->ends_at->lte($now);
                        $trialHasEnded = $locked->status === 'trialing'
                            && $locked->trial_ends_at !== null
                            && $locked->trial_ends_at->lte($now);

                        if (! $hasEnded && ! $trialHasEnded) {
                            return;
                        }

                        $locked->update([
                            'status' => 'expired',
                            'auto_renew' => false,
                        ]);

                        $hasCurrentSubscription = BusinessSubscription::query()
                            ->where('business_id', $locked->business_id)
                            ->whereIn('status', ['active', 'trialing', 'canceled'])
                            ->where(function ($query) use ($now): void {
                                $query->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                            })
                            ->where(function ($query) use ($now): void {
                                $query->where('status', '!=', 'trialing')
                                    ->orWhereNull('trial_ends_at')
                                    ->orWhere('trial_ends_at', '>', $now);
                            })
                            ->exists();

                        if (! $hasCurrentSubscription) {
                            BusinessSubscription::query()->create([
                                'business_id' => $locked->business_id,
                                'subscription_plan_id' => $freePlan->id,
                                'status' => 'active',
                                'starts_at' => $now,
                                'auto_renew' => false,
                            ]);
                        }

                        $expired++;
                    });
                }
            });

        $this->info("Expired subscriptions processed: {$expired}");

        return self::SUCCESS;
    }
}
