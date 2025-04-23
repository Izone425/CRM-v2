<?php

namespace App\Livewire;

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

class LeadOwnerChangeRequestTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getTableQuery()
    {
        return Request::query()
            ->where('status', '!=', 'approved') // 👈 exclude pending status
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
                        ->modalWidth('md')
                        ->color('warning'),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Update the lead's owner
                            $lead = Lead::find($record->lead_id);
                            $newOwner = User::find($record->requested_owner_id);

                            if ($lead && $newOwner) {
                                $lead->update([
                                    'lead_owner' => $newOwner->name,
                                ]);

                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                                if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                                    $latestActivityLog->update([
                                        'description' => 'Change Lead Owner has been Approved by Manager',
                                    ]);

                                    activity()
                                        ->causedBy(auth()->user())
                                        ->performedOn($record);
                                }
                                $record->update([
                                    'status' => 'approved',
                                ]);

                                Notification::make()
                                    ->title('Lead Owner Updated')
                                    ->body("Lead owner changed to {$newOwner->name}.")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->color('success'),
                ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.lead-owner-change-request-table');
    }
}
