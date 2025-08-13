<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\ImplementerLogs;
use App\Models\SoftwareHandover;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class ImplementerFollowUpTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Section::make('Follow-up Records')
                        ->description('View and add follow-up tasks for this Project')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->headerActions([
                            Action::make('add_follow_up')
                                ->label('Add Follow-up')
                                ->button()
                                ->color('primary')
                                ->icon('heroicon-o-plus')
                                ->form([
                                    DatePicker::make('follow_up_date')
                                        ->label('Next Follow-up Date')
                                        ->default(function() {
                                            $today = now();
                                            $daysUntilNextTuesday = (9 - $today->dayOfWeek) % 7; // 2 is Tuesday, but we add 7 to ensure positive
                                            if ($daysUntilNextTuesday === 0) {
                                                $daysUntilNextTuesday = 7; // If today is Tuesday, we want next Tuesday
                                            }
                                            return $today->addDays($daysUntilNextTuesday);
                                        })
                                        ->minDate(now())
                                        ->required(),

                                    RichEditor::make('notes')
                                        ->label('Remarks')
                                        ->disableToolbarButtons([
                                            'attachFiles',
                                            'blockquote',
                                            'codeBlock',
                                            'h2',
                                            'h3',
                                            'link',
                                            'redo',
                                            'strike',
                                            'undo',
                                        ])
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->placeholder('Add your follow-up details here...')
                                        ->required()
                                ])
                                ->modalHeading('Add New Follow-up')
                                ->modalWidth('3xl')
                                ->action(function (Lead $record, array $data) {
                                    // Find the SoftwareHandover record for this lead
                                    $softwareHandover = SoftwareHandover::where('lead_id', $record->id)->latest()->first();

                                    if (!$softwareHandover) {
                                        Notification::make()
                                            ->title('Error: Software Handover record not found')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Update the SoftwareHandover record with follow-up information
                                    $softwareHandover->update([
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                        'manual_follow_up_count' => $softwareHandover->manual_follow_up_count + 1,
                                    ]);

                                    // Create description for the follow-up
                                    $followUpDescription = 'Implementer Follow Up By ' . auth()->user()->name;

                                    // Create a new implementer_logs entry with reference to SoftwareHandover
                                    ImplementerLogs::create([
                                        'lead_id' => $record->id,
                                        'description' => $followUpDescription,
                                        'causer_id' => auth()->id(),
                                        'remark' => $data['notes'],
                                        'subject_id' => $softwareHandover->id, // Store the softwarehandover ID
                                    ]);

                                    Notification::make()
                                        ->title('Follow-up added successfully')
                                        ->success()
                                        ->send();
                                }),
                        ])
                        ->schema([
                            Card::make()
                                ->schema([
                                    View::make('components.implementer-followup-history')
                                        ->extraAttributes(['class' => 'p-0']),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
