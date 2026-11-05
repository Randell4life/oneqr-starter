<?php

namespace App\Filament\Admin\Resources\Subscriptions\Pages;

use App\Filament\Admin\Resources\Subscriptions\BusinessSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessSubscriptions extends ListRecords
{
    protected static string $resource = BusinessSubscriptionResource::class;
}
