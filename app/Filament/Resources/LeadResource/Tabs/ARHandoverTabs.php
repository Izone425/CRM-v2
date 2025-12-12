<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;

class ARHandoverTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    \Njxqlus\Filament\Components\Forms\RelationManager::make()
                        ->manager(\App\Filament\Resources\LeadResource\RelationManagers\RenewalHandoverRelationManager::class
                    ),
                ]),
        ];
    }
}
