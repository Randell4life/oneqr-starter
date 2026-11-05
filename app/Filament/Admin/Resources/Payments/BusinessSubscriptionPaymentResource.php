<?php

namespace App\Filament\Admin\Resources\Payments;

use App\Filament\Admin\Resources\Payments\Pages;
use App\Models\BusinessSubscriptionPayment;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessSubscriptionPaymentResource extends Resource
{
    protected static ?string $model = BusinessSubscriptionPayment::class;

    protected static ?string $navigationLabel = 'გადახდები';

    protected static ?string $modelLabel = 'გადახდა';

    protected static ?string $pluralModelLabel = 'გადახდები';

    protected static ?string $navigationGroup = 'ფინანსები';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('merchant_payment_id')->label('Merchant Payment ID')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('თარიღი')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('business.name')->label('ბიზნესი')->searchable(),
                TextColumn::make('plan.name')->label('ტარიფი'),
                TextColumn::make('provider')->label('პროვაიდერი')->badge(),
                TextColumn::make('status')->label('სტატუსი')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed', 'expired' => 'danger',
                        'review' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('amount')->label('თანხა')
                    ->formatStateUsing(fn ($state, BusinessSubscriptionPayment $record): string => number_format((float) $state, 2).' '.$record->currency),
                TextColumn::make('merchant_payment_id')->label('შეკვეთის კოდი')->copyable(),
                TextColumn::make('verified_at')->label('დადასტურდა')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('სტატუსი')->options([
                    'pending' => 'მოლოდინში',
                    'initiated' => 'ინიცირებული',
                    'paid' => 'წარმატებული',
                    'failed' => 'წარუმატებელი',
                    'expired' => 'ვადაგასული',
                    'review' => 'შემოწმება საჭიროა',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBusinessSubscriptionPayments::route('/')];
    }
}
