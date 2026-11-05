<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Filament\Admin\Resources\Payments\BusinessSubscriptionPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessSubscriptionPayments extends ListRecords
{
    protected static string $resource = BusinessSubscriptionPaymentResource::class;
}
