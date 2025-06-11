<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Filament\Resources\OfficeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['proposed_by'] = Auth::id();
        $data['proposed_at'] = now();
        
        // If user is ROOT, auto-approve
        if (Auth::user()?->role === \App\Enums\UserRole::ROOT) {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
