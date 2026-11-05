<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'მომხმარებლები';

    protected static ?string $modelLabel = 'მომხმარებელი';

    protected static ?string $pluralModelLabel = 'მომხმარებლები';

    protected static ?string $navigationGroup = 'პლატფორმის მართვა';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('სახელი')->searchable()->sortable(),
                TextColumn::make('email')->label('ელფოსტა')->searchable(),
                IconColumn::make('is_platform_admin')->label('ადმინისტრატორი')->boolean(),
                TextColumn::make('businesses_count')->label('ბიზნესები')->counts('businesses'),
                TextColumn::make('created_at')->label('რეგისტრაცია')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/')];
    }
}
