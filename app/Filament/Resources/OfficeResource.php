<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers\UsersRelationManager;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        
        // ROOT users can see all offices
        if ($user?->role === UserRole::ROOT) {
            return true;
        }
        
        // Administrator users can only see their own office
        return $user?->role === UserRole::ADMINISTRATOR && $user->office_id !== null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('acronym'),
                Forms\Components\TextInput::make('head_name'),
                Forms\Components\TextInput::make('designation'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('acronym')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make('trashed'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function (Office $record) {
                        $user = Auth::user();
                        
                        // ROOT users can edit any office
                        if ($user?->role === UserRole::ROOT) {
                            return true;
                        }
                        
                        // ADMINISTRATOR users can only edit their own office
                        return $user?->role === UserRole::ADMINISTRATOR && $user->office_id === $record->id;
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('users')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = Auth::user();
        
        // If user is an Administrator, only show their office
        if ($user?->role === UserRole::ADMINISTRATOR && $user->office_id) {
            $query->where('id', $user->office_id);
        }
        
        return $query;
    }
}
