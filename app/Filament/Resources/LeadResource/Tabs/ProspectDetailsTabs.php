<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandover;
use App\Models\Industry;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
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

class ProspectDetailsTabs
{
    public static function getSchema(): array
    {
        return [
            Tabs::make('Prospect Details')
                ->tabs([
                    Tabs\Tab::make('Marketing')
                        ->schema(LeadTabs::getSchema()),

                    Tabs\Tab::make('Company')
                        ->schema(CompanyTabs::getSchema()),

                    Tabs\Tab::make('HR Details')
                        ->schema(ProspectPICTabs::getSchema()),

                    Tabs\Tab::make('Refer & Earn')
                        ->schema(ReferEarnTabs::getSchema()),
                ])
                ->columnSpan(2)
                ->visible(function ($livewire) {
                    // Get current authenticated user
                    $user = auth()->user();

                    // If not a salesperson (role_id 2), always show the tabs
                    if ($user->role_id !== 2) {
                        return true;
                    }

                    // For salespeople, check if they're assigned to this lead
                    $lead = $livewire->getRecord();
                    return $lead->salesperson == $user->id;
                }),
        ];
    }
}
