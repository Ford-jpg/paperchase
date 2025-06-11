<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Office;
use App\Models\Section;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === UserRole::ROOT ||
            \Illuminate\Support\Facades\Auth::user()?->role === UserRole::ADMINISTRATOR;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->disabled(fn (Page $livewire) => $livewire instanceof EditRecord && $livewire->record->trashed())
            ->schema([
                Forms\Components\FileUpload::make('avatar')
                    ->alignCenter()
                    ->avatar(),
                Forms\Components\Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options(UserRole::class)
                            ->required()
                            ->default(UserRole::USER->value),
                        Forms\Components\Select::make('office_id')
                            ->label('Office')
                            ->searchable()
                            ->relationship('office', 'name')
                            ->getOptionLabelUsing(fn ($value): ?string => optional(Office::find($value))->name)
                            ->placeholder('Select Office')
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('section_id', null))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('acronym')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('head_name'),
                                Forms\Components\TextInput::make('designation'),
                            ])
                            ->createOptionUsing(function (array $data): Office {
                                $data['proposed_by'] = Auth::id();
                                $data['proposed_at'] = now();
                                return Office::create($data);
                            }),

                        Forms\Components\Select::make('section_id')
                            ->label('Section')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->relationship('section', 'name')
                            ->getOptionLabelUsing(fn ($value): ?string => Section::find($value)?->name)
                            ->placeholder('Select Section')
                            ->preload()
                            ->options(function (callable $get) {
                                $officeId = $get('office_id');
                                if ($officeId) {
                                    return Section::where('office_id', $officeId)->pluck('name', 'id');
                                }

                                return Section::pluck('name', 'id');
                            })
                            ->createOptionForm([
                                Forms\Components\Select::make('office_id')
                                    ->label('Office')
                                    ->relationship('office', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('head_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('designation')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): Section {
                                $data['proposed_by'] = Auth::id();
                                $data['proposed_at'] = now();
                                return Section::create($data);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('office.acronym')
                    ->label('Office')
                    ->searchable(['offices.name', 'offices.acronym'])
                    ->sortable()
                    ->tooltip(fn (User $record) => $record->office?->name),
                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deactivated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deactivated At'),
                Tables\Columns\TextColumn::make('deactivated_by')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deactivated By')
                    ->getStateUsing(fn (User $record) => $record->deactivatedByUser?->name),
                Tables\Columns\TextColumn::make('trashed')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (User $record) {
                        return $record->deleted_at ? $record->deleted_at : null;
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('deactivated_at')
                    ->label('Deactivated')
                    ->trueLabel('Active')
                    ->falseLabel('Deactivated')
                    ->queries(
                        true: fn ($query) => $query->whereNull('deactivated_at'),
                        false: fn ($query) => $query->whereNotNull('deactivated_at'),
                    ),
                Tables\Filters\TernaryFilter::make('approved_at')
                    ->label('Approved')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('approved_at'),
                        false: fn ($query) => $query->whereNull('approved_at'),
                    ),
                Tables\Filters\TrashedFilter::make('trashed')
                    ->label('Trashed'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! $record->approved_at && Auth::check() && UserResource::canApprove(Auth::user(), $record)
                    )
                    ->action(function (User $record) {
                        $record->approve();
                        Notification::make()
                            ->title('User approved successfully.')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-no-symbol')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => is_null($record->deactivated_at))
                        ->action(fn (User $record) => $record->deactivate(Auth::user())),
                    Tables\Actions\Action::make('reactivate')
                        ->label('Reactivate')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => ! is_null($record->deactivated_at))
                        ->action(fn (User $record) => $record->reactivate()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function canApprove(User $actingUser, User $userToApprove): bool
    {
        return $actingUser->role === UserRole::ROOT ||
               ($actingUser->role === UserRole::ADMINISTRATOR && $actingUser->office_id === $userToApprove->office_id);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withTrashed()
            ->with(['office', 'section', 'deactivatedByUser']);
        if (Auth::user()?->role !== UserRole::ROOT) {
            $query->where('office_id', Auth::user()->office_id)
                ->orWhere('office_id', null);
        }

        return $query;
    }
}
