<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\CancelRepairAppointmentNotification;
use App\Mail\RepairAppointmentNotification;
use App\Models\ActivityLog;
use App\Models\AdminRepair;
use App\Models\Appointment;
use App\Models\ImplementerAppointment;
use App\Models\ImplementerLogs;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\TemplateSelector;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Spatie\Activitylog\Traits\LogsActivity;
use Livewire\Attributes\On;

class ImplementerFollowUpRelationManager extends RelationManager
{
    protected static string $relationship = 'implementerLogs';


    #[On('refresh-repair-appointments')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    protected function getTableHeading(): string
    {
        return __('Implementer Follow Up');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('index')
                    ->label('NO.')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('DATE & TIME')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('DESCRIPTION')
                    ->sortable(),

                IconColumn::make('view_remark')
                    ->label('View Details')
                    ->alignCenter()
                    ->getStateUsing(fn() => true)
                    ->icon(fn () => 'heroicon-o-magnifying-glass-plus')
                    ->color(fn () => 'blue')
                    ->tooltip('View Complete Details')
                    ->extraAttributes(['class' => 'cursor-pointer'])
                    ->action(
                        Action::make('view_remarks')
                            ->label('View Details')
                            ->modalHeading('Project Assignment Details')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function ($record) {
                                $assignedBy = User::find($record->causer_id);
                                $assignedByName = $assignedBy ? $assignedBy->name : 'Unknown';

                                $html = '<div class="p-4 rounded-lg bg-gray-50">';
                                $html .= '<p class="mb-3 font-medium text-gray-900">Project Assignment Details:</p>';
                                $html .= '<div class="grid grid-cols-2 gap-4 mb-4">';
                                $html .= '<div><span class="font-medium">Type:</span> ' . e($record->description) . '</div>';
                                $html .= '<div><span class="font-medium">Assigned By:</span> ' . e($assignedByName) . '</div>';
                                $html .= '<div><span class="font-medium">Date:</span> ' . Carbon::parse($record->created_at)->format('d M Y, h:i A') . '</div>';
                                $html .= '</div>';
                                $html .= '<div class="pt-3 mt-3 border-t border-gray-200">';
                                $html .= '<p class="mb-2 font-medium text-gray-900">Assignment Notes:</p>';
                                $html .= '<div class="p-3 bg-white border border-gray-200 rounded">' . nl2br(e($record->remark)) . '</div>';
                                $html .= '</div>';
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('addFollowUp')
                    ->label(__('Add Follow Up'))
                    ->form([
                        Forms\Components\Placeholder::make('')
                            ->content(__('Fill out the following section to add a follow-up for this lead.
                                        Select a follow-up date if the lead requests to be contacted on a specific date.
                                        Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

                        Forms\Components\Textarea::make('remark')
                            ->label('Remarks')
                            ->rows(3)
                            ->autosize()
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                        Forms\Components\Grid::make(3) // 3 columns grid
                            ->schema([
                                Forms\Components\DatePicker::make('follow_up_date')
                                    ->label('Next Follow Up Date')
                                    ->required()
                                    ->placeholder('Select a follow-up date')
                                    ->default(function ($record) {
                                        $softwareHandover = $record->softwareHandover;

                                        // If software handover exists and has a follow_up_date, use next Tuesday after that date
                                        if ($softwareHandover && $softwareHandover->follow_up_date) {
                                            // Get the next Tuesday after the last follow-up date
                                            $date = Carbon::parse($softwareHandover->follow_up_date);
                                            return $date->next(Carbon::TUESDAY);
                                        }

                                        // Otherwise use next Tuesday from today
                                        return now()->next(Carbon::TUESDAY);
                                    })
                                    ->reactive(),

                                Forms\Components\Select::make('status')
                                    ->label('STATUS')
                                    ->options([
                                        'Hot' => 'Hot',
                                        'Warm' => 'Warm',
                                        'Cold' => 'Cold'
                                    ])
                                    ->default('Hot')
                                    ->required()
                                    ->visible(function ($record) {
                                        $lead = $record->lead;
                                        return in_array(Auth::user()->role_id, [2, 3]) &&
                                            $lead && $lead->stage === 'Follow Up';
                                    }),
                            ])
                    ])
                    ->color('success')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function ($record, array $data) {
                        // Get the lead from the record
                        $lead = $this->ownerRecord;

                        if (!$lead) {
                            Notification::make()
                                ->title('Error: Lead not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
                        $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);

                        // Find the SoftwareHandover record for this lead
                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->first();

                        if (!$softwareHandover) {
                            Notification::make()
                                ->title('Error: Software Handover record not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Update the SoftwareHandover record with follow-up information
                        $softwareHandover->update([
                            'follow_up_date' => $followUpDate,
                            'follow_up_counter' => true,
                            'manual_follow_up_count' => $softwareHandover->manual_follow_up_count + 1,
                        ]);

                        // Create description for the follow-up
                        $followUpDescription = 'Implementer Follow Up By ' . auth()->user()->name;

                        // Create a new implementer_logs entry with reference to SoftwareHandover
                        ImplementerLogs::create([
                            'lead_id' => $lead->id,
                            'description' => $followUpDescription,
                            'causer_id' => auth()->id(),
                            'remark' => $data['remark'],
                            'subject_id' => $softwareHandover->id, // Store the softwarehandover ID
                        ]);

                        // Send a notification
                        Notification::make()
                            ->title('Follow Up Added Successfully')
                            ->success()
                            ->send();

                        // Refresh the relation manager
                        $this->refresh();
                    }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
                ->hidden(function (ImplementerLogs $record): bool {
                    // Get the latest record ID for comparison
                    $latestRecordId = ImplementerLogs::where('lead_id', $record->lead_id)
                        ->latest('id')
                        ->first()?->id;

                    // Only show on the record with the highest/latest ID (the first row when sorted by desc)
                    return $record->id !== $latestRecordId;
                })
            ])->defaultSort('id', 'desc');
    }
}
