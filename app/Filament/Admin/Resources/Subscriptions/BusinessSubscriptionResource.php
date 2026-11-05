<?php

namespace App\Filament\Admin\Resources\Subscriptions;

use App\Filament\Admin\Resources\Subscriptions\Pages;
use App\Models\BusinessSubscription;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessSubscriptionResource extends Resource
{
    protected static ?string $model = BusinessSubscription::class;

    protected static ?string $navigationLabel = 'გამოწერები';

    protected static ?string $modelLabel = 'გამოწერა';

    protected static ?string $pluralModelLabel = 'გამოწერები';

    protected static string|\UnitEnum|null $navigationGroup = 'ფინანსები';

    protected static ?int $navigationSort = 3;

    public static function getSlug(): string
    {
        return 'subscriptions';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('business_id')->label('ბიზნესი')->relationship('business', 'name')->searchable()->preload()->required(),
            Select::make('subscription_plan_id')->label('ტარიფი')->relationship('plan', 'name')->searchable()->preload()->required(),
            Select::make('status')->label('სტატუსი')->options([
                'active' => 'აქტიური',
                'trialing' => 'საცდელი',
                'canceled' => 'გაუქმებული',
                'superseded' => 'ჩანაცვლებული',
                'expired' => 'ვადაგასული',
            ])->required(),
            DateTimePicker::make('starts_at')->label('იწყება')->required(),
            DateTimePicker::make('ends_at')->label('მთავრდება'),
            Toggle::make('auto_renew')->label('ავტომატური განახლება'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->label('ბიზნესი')->searchable(),
                TextColumn::make('plan.name')->label('ტარიფი'),
                TextColumn::make('status')->label('სტატუსი')->badge(),
                TextColumn::make('starts_at')->label('დაწყება')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('დასრულება')->dateTime('d.m.Y H:i')->sortable()->placeholder('უვადო'),
                TextColumn::make('payment_provider')->label('პროვაიდერი')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('სტატუსი')->options([
                    'active' => 'აქტიური',
                    'trialing' => 'საცდელი',
                    'canceled' => 'გაუქმებული',
                    'superseded' => 'ჩანაცვლებული',
                    'expired' => 'ვადაგასული',
                ]),
            ])
            ->recordActions([EditAction::make()->label('რედაქტირება')])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessSubscriptions::route('/'),
            'edit' => Pages\EditBusinessSubscription::route('/{record}/edit'),
        ];
    }
}
