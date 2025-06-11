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
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
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
                Tables\Columns\TextColumn::make('proposed_by.name')
                    ->label('Proposed By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proposed_at')
                    ->label('Proposed At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_by.name')
                    ->label('Approved By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Pending'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Pending'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Office $record) => $record->approved_at ? 'Approved' : 'Pending')
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make('trashed'),
                Tables\Filters\TernaryFilter::make('approved')
                    ->label('Status')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('approved_at'),
                        false: fn ($query) => $query->whereNull('approved_at'),
                    ),
            ])
            ->actions([
                ViewAction::make(),
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
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Office $record): bool => 
                        Auth::user()?->role === UserRole::ROOT && 
                        is_null($record->approved_at)
                    )
                    ->action(function (Office $record) {
                        $record->update([
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Office approved successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->role === UserRole::ROOT),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->role === UserRole::ROOT),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()?->role === UserRole::ROOT),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()?->role === UserRole::ROOT)
                        ->action(function (Collection $records) {
                            $approvedCount = 0;
                            foreach ($records as $record) {
                                if (is_null($record->approved_at)) {
                                    $record->update([
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                    $approvedCount++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("$approvedCount office(s) approved successfully.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('proposed_at', 'desc');
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
            'view' => Pages\ViewOffice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('users')
            ->with(['proposed_by', 'approved_by'])
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
