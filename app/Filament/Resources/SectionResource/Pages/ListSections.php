<?php

namespace App\Filament\Resources\SectionResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\SectionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListSections extends ListRecords
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // ROOT and ADMINISTRATOR users can create new sections
        if (Auth::user()?->role === UserRole::ROOT || Auth::user()?->role === UserRole::ADMINISTRATOR) {
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => \App\Models\Section::count()),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('approved_at'))
                ->badge(fn () => \App\Models\Section::whereNotNull('approved_at')->count()),
            'proposed' => Tab::make('Proposed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('approved_at'))
                ->badge(fn () => \App\Models\Section::whereNull('approved_at')->count()),
        ];
    }
}
