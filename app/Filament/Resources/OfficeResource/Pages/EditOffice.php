<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource;
use App\Models\Office;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOffice extends EditRecord
{
    protected static string $resource = OfficeResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $user = Auth::user();
        
        // ADMINISTRATOR users can only edit their own office
        if ($user?->role === UserRole::ADMINISTRATOR && $user->office_id !== $this->record->id) {
            abort(403, 'You can only edit your own office.');
        }
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $actions = [];

        // Only ROOT users can delete offices
        if ($user?->role === UserRole::ROOT) {
            $actions[] = ActionGroup::make([
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (Office $record) {
                        $record->delete();
                    })
                    ->successRedirectUrl(fn () => static::getResource()::getUrl('index'))
                    ->color('danger')
                    ->label('Delete'),

                Actions\Action::make('restore')
                    ->label('Restore Office')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Office $record) => $record->trashed())
                    ->action(fn (Office $record) => $record->restore())
                    ->color('success'),
                    
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (Office $record): void {
                        $record->forceDelete();
                    })
                    ->color('danger')
                    ->label('Permanently Delete'),
            ])
                ->label('Danger Actions')
                ->icon('heroicon-o-ellipsis-vertical');
        }

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
