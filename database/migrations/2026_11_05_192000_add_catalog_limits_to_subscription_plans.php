<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $limits = [
            'free' => 10,
            'starter' => 100,
            'pro' => 500,
            'business' => null,
        ];

        foreach ($limits as $slug => $limit) {
            $plan = DB::table('subscription_plans')->where('slug', $slug)->first();
            if (! $plan) {
                continue;
            }

            $current = json_decode($plan->limits, true) ?: [];
            $current['catalog_items'] = $limit;
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'limits' => json_encode($current, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('subscription_plans')->get() as $plan) {
            $current = json_decode($plan->limits, true) ?: [];
            unset($current['catalog_items']);
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'limits' => json_encode($current, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }
};
