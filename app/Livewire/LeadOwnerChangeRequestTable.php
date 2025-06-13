<?php

namespace App\Livewire;

use App\Mail\LeadOwnerChangedNotification;
use App\Models\ActivityLog;
use App\Models\Request;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadOwnerChangeRequestTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getTableQuery()
    {
        return Request::query()
            ->where('status', '=', 'pending') // 👈 exclude pending status
            ->with(['lead', 'requestedBy', 'currentOwner', 'requestedOwner']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 10, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id); // fixed: use lead_id not record id

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('requestedBy.name')->label('Requested By'),
                TextColumn::make('currentOwner.name')->label('Current Owner'),
                TextColumn::make('requestedOwner.name')->label('Requested Owner'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_reason')
                        ->label('View Reason')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn ($record) => view('components.view-reason', [
                            'reason' => $record->reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),

                        Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $lead = Lead::find($record->lead_id);
                            $newOwner = User::find($record->requested_owner_id);
                            $previousOwner = User::find($record->current_owner_id);

                            if ($lead && $newOwner && $previousOwner) {
                                // Update lead owner
                                $lead->update([
                                    'lead_owner' => $newOwner->name,
                                ]);

                                // Update activity log
                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . $newOwner->name) {
                                    $latestActivityLog->update([
                                        'description' => 'Change Lead Owner has been Approved by Manager',
                                    ]);
                                    sleep(1);
                                    activity()
                                        ->causedBy(auth()->user())
                                        ->performedOn($lead)
                                        ->log('Lead Owner Changed via Approval');
                                }

                                // Mark the request as approved
                                $record->update([
                                    'status' => 'approved',
                                ]);

                                // Prepare email content
                                $emailContent = [
                                    'previousOwnerName' => $previousOwner->name,
                                    'newOwnerName' => $newOwner->name,
                                    'lead' => [
                                        'lead_code' => $lead->lead_code,
                                        'company' => $lead->companyDetail->company_name ?? $lead->name,
                                        'phone' => $lead->companyDetail->phone ?? $lead->phone,
                                        'email' => $lead->companyDetail->email ?? $lead->email,
                                        'salespersonEmail' => 'faiz@timeteccloud.com',
                                        'salespersonName' => 'Faiz',
                                    ],
                                ];

                                try {
                                    Mail::to([$previousOwner->email, $newOwner->email, 'faiz@timeteccloud.com'])
                                        ->send(new LeadOwnerChangedNotification($emailContent, 'faiz@timeteccloud.com'));

                                    Notification::make()
                                        ->title('Lead Owner Updated')
                                        ->body("Lead owner changed to {$newOwner->name}. Notification email sent.")
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error("Failed to send lead owner change email: " . $e->getMessage());

                                    Notification::make()
                                        ->title('Lead Owner Updated (Email Failed)')
                                        ->body("Lead owner changed, but email notification failed.")
                                        ->danger()
                                        ->send();
                                }
                            }
                        })
                        ->color('success'),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Lead Owner Change Request')
                        ->modalDescription('Are you sure you want to reject this request?')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'rejected',
                            ]);

                            $lead = Lead::find($record->lead_id);
                            $requestedOwner = User::find($record->requested_owner_id);
                            $currentOwner = User::find($record->current_owner_id);

                            if ($lead) {
                                // Log activity on the lead
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->withProperties([
                                        'requested_owner' => $record->requested_owner_id,
                                        'current_owner' => $record->current_owner_id,
                                        'reason' => $record->reason,
                                    ])
                                    ->log('Lead Owner Change Request Rejected');

                                // Prepare email content
                                $emailContent = [
                                    'previousOwnerName' => $currentOwner?->name ?? 'Unknown',
                                    'newOwnerName' => $requestedOwner?->name ?? 'Unknown',
                                    'lead' => [
                                        'lead_code' => $lead->lead_code,
                                        'company' => $lead->companyDetail->company_name ?? $lead->name,
                                        'phone' => $lead->companyDetail->phone ?? $lead->phone,
                                        'email' => $lead->companyDetail->email ?? $lead->email,
                                        'salespersonEmail' => 'faiz@timeteccloud.com',
                                        'salespersonName' => 'Faiz',
                                    ],
                                    'rejected' => true,
                                    'reason' => $record->reason,
                                ];

                                try {
                                    // Send to both involved users and Faiz
                                    Mail::to([
                                            $currentOwner?->email,
                                            $requestedOwner?->email,
                                            'faiz@timeteccloud.com'
                                        ])
                                        ->send(new LeadOwnerChangedNotification($emailContent, 'faiz@timeteccloud.com'));

                                    Notification::make()
                                        ->title('Request Rejected')
                                        ->body('The lead owner change request has been rejected. Notification email sent.')
                                        ->danger()
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error("Failed to send rejection email: " . $e->getMessage());

                                    Notification::make()
                                        ->title('Request Rejected (Email Failed)')
                                        ->body('Request was rejected, but the email could not be sent.')
                                        ->danger()
                                        ->send();
                                }
                            }
                        })
                        ->color('danger'),
                ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.lead-owner-change-request-table');
    }
}
