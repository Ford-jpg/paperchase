<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\OfficeResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => \App\Models\Office::count()),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('approved_at'))
                ->badge(fn () => \App\Models\Office::whereNotNull('approved_at')->count()),
            'proposed' => Tab::make('Proposed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('approved_at'))
                ->badge(fn () => \App\Models\Office::whereNull('approved_at')->count()),
        ];
    }
}
