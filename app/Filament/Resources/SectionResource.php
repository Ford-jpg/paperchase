<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\SectionResource\Pages;
use App\Models\Section;
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

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === UserRole::ROOT ||
            Auth::user()?->role === UserRole::ADMINISTRATOR;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name'),
                Forms\Components\Select::make('office_id')
                    ->relationship('office', 'name')
                    ->preload()
                    ->required()
                    ->searchable()
                    ->placeholder('Select Office'),
                Forms\Components\TextInput::make('head_name'),
                Forms\Components\TextInput::make('designation'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->sortable()
                    ->searchable(),
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
                    ->getStateUsing(fn (Section $record) => $record->approved_at ? 'Approved' : 'Pending')
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('head_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('designation')
                    ->sortable()
                    ->searchable(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Section $record): bool => 
                        Auth::user()?->role === UserRole::ROOT && 
                        is_null($record->approved_at)
                    )
                    ->action(function (Section $record) {
                        $record->update([
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Section approved successfully.')
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
                                ->title("$approvedCount section(s) approved successfully.")
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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
            'view' => Pages\ViewSection::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['office', 'proposedBy', 'approvedBy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = Auth::user();
        
        // If user is an Administrator, only show sections in their office
        if ($user?->role === UserRole::ADMINISTRATOR && $user->office_id) {
            $query->where('office_id', $user->office_id);
        }
        
        return $query;
    }
}
