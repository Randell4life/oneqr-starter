<?php

namespace App\Filament\Admin\Resources\Businesses;

use App\Filament\Admin\Resources\Businesses\Pages;
use App\Models\Business;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationLabel = 'ბიზნესები';

    protected static ?string $modelLabel = 'ბიზნესი';

    protected static ?string $pluralModelLabel = 'ბიზნესები';

    protected static ?string $navigationGroup = 'პლატფორმის მართვა';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('owner_id')
                ->label('მფლობელი')
                ->relationship('owner', 'email')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')->label('ბიზნესის სახელი')->required()->maxLength(255),
            TextInput::make('slug')->label('მისამართის იდენტიფიკატორი')->required()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('business_type')->label('კატეგორია')->options([
                'restaurant' => 'რესტორანი',
                'retail' => 'ვაჭრობა',
                'services' => 'მომსახურება',
                'hotel' => 'სასტუმრო',
                'other' => 'სხვა',
            ])->required(),
            TextInput::make('email')->label('ელფოსტა')->email()->maxLength(255),
            TextInput::make('phone')->label('ტელეფონი')->maxLength(40),
            TextInput::make('website')->label('ვებგვერდი')->url()->maxLength(255),
            TextInput::make('timezone')->label('დროის სარტყელი')->default('Asia/Tbilisi')->required(),
            TextInput::make('default_locale')->label('ენა')->default('ka')->required()->maxLength(5),
            Select::make('status')->label('სტატუსი')->options([
                'pending' => 'მოლოდინში',
                'active' => 'აქტიური',
                'suspended' => 'შეჩერებული',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('ბიზნესი')->searchable()->sortable(),
                TextColumn::make('owner.email')->label('მფლობელი')->searchable(),
                TextColumn::make('business_type')->label('კატეგორია')->badge(),
                TextColumn::make('status')->label('სტატუსი')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('რეგისტრაცია')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('სტატუსი')->options([
                    'pending' => 'მოლოდინში',
                    'active' => 'აქტიური',
                    'suspended' => 'შეჩერებული',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('რედაქტირება'),
                DeleteAction::make()->label('რბილი წაშლა')->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }
}
