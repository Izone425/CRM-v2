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
                    Section::make('Renewal Handover')
                        ->schema([
                            Placeholder::make('renewal_info')
                                ->content('This section will lets user to choose PI and then generate Autocount HRDF Invoice.')
                                ->hiddenLabel(),
                        ])
                        ->collapsible(),
                ])
        ];
    }
}
