<?php

namespace App\Filament\Admin\Resources\SubscriptionPlans;

use App\Filament\Admin\Resources\SubscriptionPlans\Pages;
use App\Models\SubscriptionPlan;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationLabel = 'ტარიფები';

    protected static ?string $modelLabel = 'ტარიფი';

    protected static ?string $pluralModelLabel = 'ტარიფები';

    protected static ?string $navigationGroup = 'ფინანსები';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('დასახელება')->required()->maxLength(80),
            TextInput::make('slug')->label('სისტემური კოდი')->required()->unique(ignoreRecord: true)->maxLength(40),
            Textarea::make('description')->label('აღწერა')->rows(3),
            TextInput::make('monthly_price')->label('თვიური ფასი')->numeric()->minValue(0)->prefix('₾')->nullable(),
            Select::make('currency')->label('ვალუტა')->options([
                'GEL' => 'GEL — ლარი',
                'USD' => 'USD — დოლარი',
                'EUR' => 'EUR — ევრო',
            ])->required()->default('GEL'),
            KeyValue::make('limits')->label('რესურსების ლიმიტები')
                ->keyLabel('რესურსის კოდი')
                ->valueLabel('რაოდენობა')
                ->helperText('მაგალითი: qr_codes = 5, branches = 1, team_members = 2. null ან ცარიელი მნიშვნელობა შეუზღუდავია.'),
            KeyValue::make('features')->label('ფუნქციები')
                ->keyLabel('ფუნქციის კოდი')
                ->valueLabel('აღწერა'),
            Toggle::make('is_active')->label('აქტიური')->default(true),
            TextInput::make('sort_order')->label('ჩვენების რიგი')->numeric()->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('ტარიფი')->searchable()->sortable(),
                TextColumn::make('slug')->label('კოდი')->badge(),
                TextColumn::make('monthly_price')->label('თვიური ფასი')
                    ->formatStateUsing(fn ($state, SubscriptionPlan $record): string => $state === null ? 'ფასი განსაზღვრული არ არის' : number_format((float) $state, 2).' '.$record->currency),
                IconColumn::make('is_active')->label('აქტიური')->boolean(),
                TextColumn::make('sort_order')->label('რიგი')->sortable(),
                TextColumn::make('subscriptions_count')->label('გამოწერები')->counts('subscriptions'),
            ])
            ->recordActions([
                EditAction::make()->label('რედაქტირება'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
