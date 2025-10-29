<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class ImplementerHardwareHandoverTabs
{
    public static function getSchema(): array
    {
        return [
            Tabs::make('Handovers')
                ->tabs([
                    Tabs\Tab::make('Hardware Handover')
                        ->schema([
                            \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                ->manager(\App\Filament\Resources\LeadResource\RelationManagers\HHTableRelationManager::class
                            ),
                        ]),
                ])
                ->columnSpan(2),
        ];
    }
}
