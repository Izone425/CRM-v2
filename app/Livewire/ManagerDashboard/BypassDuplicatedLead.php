<?php

namespace App\Livewire\ManagerDashboard;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
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

class BypassDuplicatedLead extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getTableQuery()
    {
        return Request::query()
            ->where('status', 'pending')
            ->where('request_type', 'bypass_duplicate')
            ->with(['lead.companyDetail', 'requestedBy']);
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
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead_id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lead.lead_code')
                    ->label('Lead Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Request Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_reason')
                        ->label('View Details')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Bypass Duplicate Request Details')
                        ->modalContent(function ($record) {
                            $duplicateInfo = null;
                            if ($record->duplicate_info) {
                                $duplicateInfo = json_decode($record->duplicate_info, true);
                            }

                            return view('components.view-bypass-duplicate-details', [
                                'reason' => $record->reason,
                                'duplicateInfo' => $duplicateInfo,
                                'record' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('7xl')
                        ->color('warning'),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Bypass Duplicate Request')
                        ->modalDescription('This will assign the lead to the requestor and mark it as Active/Transfer/New.')
                        ->action(function ($record) {
                            $lead = Lead::find($record->lead_id);
                            $requestor = User::find($record->requested_by);
                            $approvedBy = auth()->user();

                            if ($lead && $requestor) {
                                // Update lead
                                $lead->updateQuietly([
                                    'lead_owner' => $requestor->name,
                                    'categories' => LeadCategoriesEnum::ACTIVE->value,
                                    'stage' => LeadStageEnum::TRANSFER->value,
                                    'lead_status' => LeadStatusEnum::NEW->value,
                                ]);

                                // Update request status
                                $record->update([
                                    'status' => 'approved',
                                ]);

                                // Send approval email to requestor
                                try {
                                    $companyName = $lead->companyDetail->company_name ?? 'N/A';
                                    $emailData = [
                                        'companyName' => $companyName,
                                        'leadCode' => $lead->lead_code,
                                        'leadId' => $lead->id,
                                        'requestorName' => $requestor->name,
                                        'approvedByName' => $approvedBy->name,
                                        'reason' => $record->reason,
                                        'approvedAt' => now()->format('d M Y, h:i A'),
                                        'leadUrl' => url('admin/leads/' . Encryptor::encrypt($lead->id)),
                                    ];

                                    Mail::send('emails.bypass-duplicate-approval', $emailData, function ($message) use ($requestor, $companyName) {
                                        $message->to($requestor->email)
                                            ->subject('Bypass Duplicate Request Approved - ' . $companyName);
                                    });

                                    Log::info('Bypass duplicate approval email sent', [
                                        'request_id' => $record->id,
                                        'lead_id' => $lead->id,
                                        'requestor_email' => $requestor->email,
                                        'approved_by' => $approvedBy->name,
                                    ]);

                                    Notification::make()
                                        ->title('Request Approved')
                                        ->body("Lead assigned to {$requestor->name}. Status updated to Active/Transfer/New. Email notification sent.")
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Failed to send bypass duplicate approval email', [
                                        'error' => $e->getMessage(),
                                        'request_id' => $record->id,
                                        'requestor_email' => $requestor->email,
                                    ]);

                                    Notification::make()
                                        ->title('Request Approved')
                                        ->body("Lead assigned to {$requestor->name}. Status updated to Active/Transfer/New. But failed to send email notification.")
                                        ->warning()
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Lead or requestor not found.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->color('success'),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Bypass Duplicate Request')
                        ->modalDescription('Are you sure you want to reject this bypass request? The requestor will be notified via email.')
                        ->action(function ($record) {
                            $lead = Lead::find($record->lead_id);
                            $requestor = User::find($record->requested_by);
                            $rejectedBy = auth()->user();

                            if ($lead && $requestor) {
                                // Update request status
                                $record->update([
                                    'status' => 'rejected',
                                ]);

                                // Send rejection email to requestor
                                try {
                                    $companyName = $lead->companyDetail->company_name ?? 'N/A';
                                    $emailData = [
                                        'companyName' => $companyName,
                                        'leadCode' => $lead->lead_code,
                                        'requestorName' => $requestor->name,
                                        'rejectedByName' => $rejectedBy->name,
                                        'reason' => $record->reason,
                                        'rejectedAt' => now()->format('d M Y, h:i A'),
                                    ];

                                    Mail::send('emails.bypass-duplicate-rejection', $emailData, function ($message) use ($requestor, $companyName) {
                                        $message->to($requestor->email)
                                            ->subject('Bypass Duplicate Request Rejected - ' . $companyName);
                                    });

                                    Log::info('Bypass duplicate rejection email sent', [
                                        'request_id' => $record->id,
                                        'lead_id' => $lead->id,
                                        'requestor_email' => $requestor->email,
                                        'rejected_by' => $rejectedBy->name,
                                    ]);

                                    Notification::make()
                                        ->title('Request Rejected')
                                        ->body("The bypass duplicate request has been rejected. Email notification sent to {$requestor->name}.")
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Failed to send bypass duplicate rejection email', [
                                        'error' => $e->getMessage(),
                                        'request_id' => $record->id,
                                        'requestor_email' => $requestor->email,
                                    ]);

                                    Notification::make()
                                        ->title('Request Rejected')
                                        ->body('The request has been rejected, but failed to send email notification.')
                                        ->warning()
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Lead or requestor not found.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->color('danger'),
                ])->button()
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.manager-dashboard.bypass-duplicated-lead');
    }
}
