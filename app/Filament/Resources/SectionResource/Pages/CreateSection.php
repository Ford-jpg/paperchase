<?php

namespace App\Filament\Resources\SectionResource\Pages;

use App\Filament\Resources\SectionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    protected bool $createAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['proposed_by'] = Auth::id();
        $data['proposed_at'] = now();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
