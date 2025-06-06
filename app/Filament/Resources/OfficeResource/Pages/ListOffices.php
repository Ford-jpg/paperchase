<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListOffices extends ListRecords
{
    protected static string $resource = OfficeResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Only ROOT users can create new offices
        if (Auth::user()?->role === UserRole::ROOT) {
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }
}
