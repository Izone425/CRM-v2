<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\HardwareHandover;
use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Microsoft\Graph\Graph;

class ImplementerRequestApproved extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getImplementerPendingRequests()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = \App\Models\ImplementerAppointment::query()
                ->where('request_status', 'Approved')
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->with(['lead', 'lead.companyDetail']);

        // Apply implementer filtering based on selected user
        if ($this->selectedUser === 'all-implementer') {
            // Show all implementer requests
        }
        elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer', $user->name);
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser->role_id === 4) {
                $query->where('implementer', $currentUser->name);
            }
        }

        return $query;
    }
    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getImplementerPendingRequests())
            ->defaultSort('date', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4, 5])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('Filter by Session Type')
                    ->options([
                        'IMPLEMENTATION SESSION' => 'Implementation Session',
                        'TRAINING SESSION' => 'Training Session',
                        'WEEKLY FOLLOW UP SESSION' => 'Weekly Follow Up Session',
                        'DATA MIGRATION' => 'Data Migration',
                        'LICENSE CERTIFICATION' => 'License Certification'
                    ])
                    ->placeholder('All Session Types')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Request ID')
                    ->formatStateUsing(function ($state) {
                        return 'IMP_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return url('admin/leads/' . $encryptedId);
                        }

                        return null;
                    })
                    ->getStateUsing(function (ImplementerAppointment $record): string {
                        // Fallback mechanism if relation is not available
                        if ($record->lead && $record->lead->companyDetail) {
                            return $record->lead->companyDetail->company_name ?? 'N/A';
                        } elseif ($record->company_name) {
                            return $record->company_name;
                        }
                        return 'N/A';
                    })
                    ->openUrlInNewTab()
                    ->color(function ($record) {
                        $company = CompanyDetail::where('company_name', $record->company_name)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),
                TextColumn::make('type')
                    ->label('Session Type')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('request_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'CANCELLED' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(function() {
                            $user = auth()->user();
                            return !($user->role_id === 3 || $user->id === 26);
                        })
                        ->action(function (\App\Models\ImplementerAppointment $record) {
                            // Call the cancelAppointment method with the appointment ID
                            $this->cancelAppointment($record->id);
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('batchCancel')
                    ->label('Cancel Selected')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(function() {
                        $user = auth()->user();
                        return !($user->role_id === 3 || $user->role_id === 5);
                    })
                    ->modalHeading('Cancel Selected Requests')
                    ->modalDescription('Are you sure you want to cancel all selected requests? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Cancel All')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $count = 0;
                        $errors = 0;

                        foreach ($records as $record) {
                            try {
                                // Cancel appointment using existing method
                                $this->cancelAppointment($record->id);
                                $count++;
                            } catch (\Exception $e) {
                                Log::error('Failed to cancel appointment in batch: ' . $e->getMessage(), [
                                    'record_id' => $record->id
                                ]);
                                $errors++;
                            }
                        }

                        if ($count > 0) {
                            Notification::make()
                                ->title("$count appointments cancelled successfully")
                                ->success()
                                ->send();
                        }

                        if ($errors > 0) {
                            Notification::make()
                                ->title("$errors appointments failed to cancel")
                                ->danger()
                                ->send();
                        }
                    })
            ]);
    }


    public function cancelAppointment($appointmentId)
    {
        $appointment = \App\Models\ImplementerAppointment::find($appointmentId);

        if (!$appointment) {
            Notification::make()
                ->title('Appointment not found')
                ->danger()
                ->send();
            return;
        }

        try {
            // Update status to Cancelled
            $appointment->status = 'Cancelled';
            $appointment->request_status = 'CANCELLED';

            // Cancel Teams meeting if exists
            if ($appointment->event_id) {
                $eventId = $appointment->event_id;

                // Get implementer's email instead of using organizer_email
                $implementer = User::where('name', $appointment->implementer)->first();

                if ($implementer && $implementer->email) {
                    $implementerEmail = $implementer->email;

                    try {
                        $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                        $graph = new Graph();
                        $graph->setAccessToken($accessToken);

                        // Cancel the Teams meeting using implementer's email
                        $graph->createRequest("DELETE", "/users/$implementerEmail/events/$eventId")->execute();

                        Notification::make()
                            ->title('Teams Meeting Cancelled Successfully')
                            ->warning()
                            ->body('The meeting has been cancelled in Microsoft Teams.')
                            ->send();

                    } catch (\Exception $e) {
                        Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                            'event_id' => $eventId,
                            'implementer' => $implementerEmail,
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('Failed to Cancel Teams Meeting')
                            ->warning()
                            ->body('The appointment was cancelled, but there was an error cancelling the Teams meeting: ' . $e->getMessage())
                            ->send();
                    }
                } else {
                    Log::error('Failed to cancel Teams meeting: Implementer email not found', [
                        'event_id' => $eventId,
                        'implementer_name' => $appointment->implementer
                    ]);

                    Notification::make()
                        ->title('Failed to Cancel Teams Meeting')
                        ->warning()
                        ->body('The appointment was cancelled, but the implementer email was not found.')
                        ->send();
                }
            }

            $appointment->save();

            // Send email notification about cancellation if needed
            $this->sendCancellationEmail($appointment);

            Notification::make()
                ->title('Appointment cancelled successfully')
                ->success()
                ->send();

            // Refresh tables
            $this->dispatch('refresh-implementer-tables');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error cancelling appointment')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function sendCancellationEmail($appointment)
    {
        try {
            $companyDetail = null;
            if ($appointment->lead_id) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $appointment->lead_id)->first();
            }

            $companyName = $companyDetail ? $companyDetail->company_name :
                ($appointment->title ?: 'Unknown Company');

            $recipients = [];

            // Add attendees from the appointment
            if ($appointment->required_attendees) {
                $attendeeEmails = array_map('trim', explode(';', $appointment->required_attendees));
                foreach ($attendeeEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $email;
                    }
                }
            }

            // Get authenticated user's email for sender
            $authUser = auth()->user();
            $senderEmail = $authUser->email;
            $senderName = $authUser->name;

            // Prepare email data
            $emailData = [
                'appointmentType' => $appointment->type,
                'companyName' => $companyName,
                'date' => Carbon::parse($appointment->date)->format('d F Y'),
                'time' => Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' .
                        Carbon::parse($appointment->end_time)->format('g:i A'),
                'implementer' => $appointment->implementer,
            ];

            if (count($recipients) > 0) {
                \Illuminate\Support\Facades\Mail::send(
                    'emails.implementer_appointment_cancelled',
                    ['content' => $emailData],
                    function ($message) use ($recipients, $senderEmail, $senderName, $companyName, $appointment) {
                        $message->from($senderEmail, $senderName)
                            ->to($recipients)
                            ->subject("CANCELLED: TIMETEC IMPLEMENTER APPOINTMENT | {$appointment->type} | {$companyName}");
                    }
                );
            }
        } catch (\Exception $e) {
            Log::error("Email sending failed for cancelled implementer appointment: Error: {$e->getMessage()}");
        }
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-request-approved');
    }
}
