<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    public function mount(): void
    {
        // Only ROOT users can create offices
        if (Auth::user()?->role !== UserRole::ROOT) {
            abort(403, 'Unauthorized');
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
