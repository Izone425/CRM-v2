<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ARQuotationTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Status')
            ->icon('heroicon-o-information-circle')
            ->extraAttributes([
                'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
            ])
            ->schema([
                Grid::make(1) // Single column in the right-side section
                    ->schema([
                        View::make('components.quotation-forecast'),
                        ])
                ]),
            \Njxqlus\Filament\Components\Forms\RelationManager::make()
                ->manager(\App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager::class,
            ),
        ];
    }
}
