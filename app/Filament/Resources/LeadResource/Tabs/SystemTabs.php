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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SystemTabs
{
    public static function getSchema(): array
    {
        return [
            Tabs::make('Phases')
                ->tabs([
                    Tabs\Tab::make('Phase 1')
                        ->schema([
                            Section::make('Phase 1')
                                ->description(fn ($record) =>
                                    $record && $record->systemQuestion
                                        ? 'Updated by ' . ($record->systemQuestion->causer_name ?? 'Unknown') . ' on ' .
                                        ($record->systemQuestion->updated_at?->format('F j, Y, g:i A') ?? 'N/A')
                                        : null
                                )
                                ->schema([
                                    View::make('components.system-questions-phase1')
                                ])
                                ->headerActions([
                                    Actions\Action::make('update')
                                        ->label('Update')
                                        ->color('primary')
                                        ->modalHeading('Update Data')
                                        ->visible(function ($record) {
                                            $demoAppointment = $record->demoAppointment()
                                                ->latest()
                                                ->first();

                                            if (!$demoAppointment) {
                                                return false;
                                            }

                                            if ($demoAppointment->status !== 'Done') {
                                                return true;
                                            }

                                            if (auth()->id() === 12) {
                                                return true;
                                            }

                                            return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                        })
                                        ->form([
                                            Textarea::make('modules')
                                                ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->modules),
                                            Textarea::make('existing_system')
                                                ->label('2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->existing_system),
                                            Textarea::make('usage_duration')
                                                ->label('3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->usage_duration),
                                            DatePicker::make('expired_date')
                                                ->label('4. WHEN IS THE EXPIRED DATE?')
                                                ->default(fn ($record) => $record?->systemQuestion?->expired_date),
                                            Textarea::make('reason_for_change')
                                                ->label('5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?')
                                                ->autosize()
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->reason_for_change)
                                                ->rows(3),
                                            Textarea::make('staff_count')
                                                ->label('6. HOW MANY STAFF DO YOU HAVE?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->staff_count),
                                            Select::make('hrdf_contribution')
                                                ->label('7. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                ->options([
                                                    'Yes' => 'Yes',
                                                    'No' => 'No',
                                                ])
                                                ->default(fn ($record) => $record?->systemQuestion?->hrdf_contribution),
                                            Textarea::make('additional')
                                                ->label('8. ADDITIONAL QUESTIONS?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestion?->additional),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            // Retrieve the current lead's systemQuestion
                                            $record = $lead->systemQuestion;

                                            if ($record) {
                                                // Include causer_id in the data
                                                $data['causer_name'] = auth()->user()->name;
                                                $record->updated_at_phase_1 = now();

                                                // Update the existing SystemQuestion record
                                                $record->update($data);

                                                Notification::make()
                                                    ->title('Updated Successfully')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                // Add causer_id to the data for the new record
                                                $data['causer_name'] = auth()->user()->name;

                                                // Create a new SystemQuestion record via the relation
                                                $lead->systemQuestion()->create($data);

                                                Notification::make()
                                                    ->title('Created Successfully')
                                                    ->success()
                                                    ->send();
                                            }
                                        }),
                                ])
                        ]),
                    Tabs\Tab::make('Phase 2')
                        ->schema([
                            Section::make('Phase 2')
                                ->description(function ($record) {
                                    if ($record && $record->systemQuestionPhase2 && !empty($record->systemQuestionPhase2->updated_at)) {
                                        return 'Updated by ' . ($record->systemQuestionPhase2->causer_name ?? 'Unknown') . ' on ' .
                                            \Carbon\Carbon::parse($record->systemQuestionPhase2->updated_at)->format('F j, Y, g:i A');
                                    }

                                    return null; // Return null if no update exists
                                })
                                ->schema([
                                    View::make('components.system-questions-phase2')
                                ])
                                ->headerActions([
                                    Actions\Action::make('update_phase2')
                                        ->label('Update')
                                        ->color('primary')
                                        ->modalHeading('Update Data')
                                        ->visible(function ($record) {
                                            $demoAppointment = $record->demoAppointment()
                                                ->latest()
                                                ->first();

                                            if (!$demoAppointment) {
                                                return false;
                                            }

                                            if ($demoAppointment->status !== 'Done') {
                                                return true;
                                            }

                                            if (auth()->id() === 12) {
                                                return true;
                                            }

                                            return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                        })
                                        ->form([
                                            Textarea::make('support')
                                                ->label('1.  PROSPECT QUESTION – NEED TO REFER SUPPORT TEAM.')
                                                ->autosize()
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase2?->support)
                                                ->rows(3),
                                            Textarea::make('product')
                                                ->label('2. PROSPECT CUSTOMIZATION – NEED TO REFER PRODUCT TEAM.')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase2?->product),
                                            Textarea::make('additional')
                                                ->label('3. ADDITIONAL QUESTIONS?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase2?->additional),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            // Retrieve the current lead's systemQuestion
                                            $record = $lead->systemQuestionPhase2;

                                            if ($record) {
                                                // Include causer_id in the data
                                                $data['causer_name'] = auth()->user()->name;

                                                // Update the existing SystemQuestion record
                                                $record->update($data);

                                                Notification::make()
                                                    ->title('Updated Successfully')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                // Add causer_id to the data for the new record
                                                $data['causer_name'] = auth()->user()->name;

                                                // Create a new SystemQuestion record via the relation
                                                $lead->systemQuestionPhase2()->create($data);

                                                Notification::make()
                                                    ->title('Created Successfully')
                                                    ->success()
                                                    ->send();
                                            }
                                        }),
                                ])
                        ]),
                    Tabs\Tab::make('Phase 3')
                        ->schema([
                            Section::make('Phase 3')
                                ->description(function ($record) {
                                    if ($record && $record->systemQuestionPhase3 && !empty($record->systemQuestionPhase3->updated_at)) {
                                        return 'Updated by ' . ($record->systemQuestionPhase3->causer_name ?? 'Unknown') . ' on ' .
                                            \Carbon\Carbon::parse($record->systemQuestionPhase3->updated_at)->format('F j, Y, g:i A');
                                    }

                                    return null; // Return null if no update exists
                                })
                                ->schema([
                                    View::make('components.system-questions-phase3')
                                ])
                                ->headerActions([
                                    Actions\Action::make('update_phase3')
                                        ->label('Update')
                                        ->color('primary')
                                        ->modalHeading('Update Data')
                                        ->visible(function ($record) {
                                            $demoAppointment = $record->demoAppointment()
                                                ->latest()
                                                ->first();

                                            if (!$demoAppointment) {
                                                return false;
                                            }

                                            if ($demoAppointment->status !== 'Done') {
                                                return true;
                                            }

                                            if (auth()->id() === 12) {
                                                return true;
                                            }

                                            return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                        })
                                        ->form([
                                            Textarea::make('percentage')
                                                ->label('1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?')
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->autosize()
                                                ->rows(3)
                                                ->default(fn ($record) => $record?->systemQuestionPhase3?->percentage),
                                            Textarea::make('vendor')
                                                ->label('2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase3?->vendor),
                                            Textarea::make('plan')
                                                ->label('3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase3?->plan),
                                            Textarea::make('finalise')
                                                ->label('4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase3?->finalise),
                                            Textarea::make('additional')
                                                ->label('5. ADDITIONAL QUESTIONS?')
                                                ->autosize()
                                                ->rows(3)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->default(fn ($record) => $record?->systemQuestionPhase3?->additional),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            // Retrieve the current record's systemQuestionPhase3 relation
                                            $record = $lead->systemQuestionPhase3;

                                            if ($record) {
                                                // Add causer_name and updated_at to the data array
                                                $data['causer_name'] = auth()->user()->name;
                                                $data['updated_at'] = now();

                                                // Update the existing record properly
                                                $record->update($data);

                                                Notification::make()
                                                    ->title('Updated Successfully')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                // If no record exists, add causer_name to the data array
                                                $data['causer_name'] = auth()->user()->name;

                                                // Create a new record via the relationship
                                                $lead->systemQuestionPhase3()->create($data);

                                                Notification::make()
                                                    ->title('Created Successfully')
                                                    ->success()
                                                    ->send();
                                            }
                                        }),
                                ])
                        ]),
            ])
        ];
    }
}
