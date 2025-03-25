<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Enums\LeadStatusEnum;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Lead;
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
use Livewire\Attributes\On;

class PrTodaySalespersonTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getTodayProspects()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser');

        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::query()
            ->where('salesperson', $salespersonId) // Filter by salesperson
            ->where('categories', '!=', 'Inactive')
            ->whereDate('follow_up_date', '=', today())
            ->selectRaw('*, DATEDIFF(NOW(), follow_up_date) as pending_days')
            ->where('follow_up_counter', true);

    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getTodayProspects())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) =>
                        '<a href="' . url('admin/leads/' . \App\Classes\Encryptor::encrypt($record->id)) . '"
                            target="_blank"
                            class="inline-block"
                            style="color:#338cf0;">
                            ' . strtoupper(Str::limit($state ?? 'N/A', 10, '...')) . '
                        </a>'
                    )
                    ->html(),
                TextColumn::make('lead_status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->default('0')
                    ->formatStateUsing(fn ($state) => $state . ' ' . ($state == 0 ? 'Day' : 'Days'))
            ])
            ->actions([
                ActionGroup::make([
                    // LeadActions::getAddQuotationAction()
                    //     ->visible(fn (?Lead $lead) => $lead && ($lead->lead_status === 'RFQ-Transfer' || $lead->lead_status === 'RFQ-Follow Up')),
                    // LeadActions::getAddDemoAction()
                    //     ->visible(fn (?Lead $lead) => $lead && ($lead->lead_status === 'RFQ-Transfer' ||
                    //     $lead->lead_status === 'Demo Cancelled' || $lead->lead_status === 'Pending Demo')),

                    // LeadActions::getDoneDemoAction()
                    //     ->visible(fn (?Lead $lead) => $lead && $lead->lead_status === 'Demo-Assigned'),
                    // LeadActions::getCancelDemoAction()
                    //     ->visible(fn (?Lead $lead) => $lead && $lead->lead_status === 'Demo-Assigned'),
                    // LeadActions::getQuotationFollowUpAction()
                    //     ->visible(function (Lead $lead) {
                    //         $latestActivityLog = $lead->activityLogs()->latest()->first();

                    //         if (!$latestActivityLog) {
                    //             return false;
                    //         }

                    //         $attributes = json_decode($latestActivityLog->properties, true)['attributes'] ?? [];

                    //         $leadStatus = data_get($attributes, 'lead_status');

                    //         $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    //             ->orderByDesc('created_at')
                    //             ->first();

                    //         if($leadStatus == LeadStatusEnum::PENDING_DEMO->value){
                    //             return false;
                    //         }

                    //         if(str_contains($latestActivityLog->description, 'Quotation Sent.')){
                    //             return true;
                    //         }

                    //         return ($leadStatus === LeadStatusEnum::HOT->value ||
                    //             $leadStatus === LeadStatusEnum::WARM->value ||
                    //             $leadStatus === LeadStatusEnum::COLD->value) &&
                    //             $latestActivityLog->description !== '4th Quotation Transfer Follow Up' &&
                    //             $latestActivityLog->description !== 'Order Uploaded. Pending Approval to close lead.';
                    //     }),
                    // LeadActions::getNoResponseAction()
                    //     ->visible(function (Lead $lead) {
                    //         $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    //             ->orderByDesc('created_at')
                    //             ->first();

                    //         if ($latestActivityLog) {
                    //             // Check if the latest activity log description needs updating
                    //             if ($lead->call_attempt >= 4 || $latestActivityLog->description == '4th Lead Owner Follow Up (Auto Follow Up Stop)'||
                    //                 $latestActivityLog->description == '4th Salesperson Transfer Follow Up' ||
                    //                 $latestActivityLog->description == 'Demo Cancelled. 4th Demo Cancelled Follow Up' ||
                    //                 $latestActivityLog->description == '4th Quotation Transfer Follow Up') {
                    //                 return true; // Show button
                    //             }
                    //         }

                    //         return false; // Default: Hide button
                    //     }),
                    LeadActions::getAddFollowUp(),
                        // ->visible(function (Lead $lead) {
                        //     $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        //         ->orderByDesc('created_at')
                        //         ->first();

                        //     if ($latestActivityLog) {
                        //         // Check if the latest activity log description needs updating
                        //         if ($lead->call_attempt >= 4 || $lead->lead_status =='Hot' || $lead->lead_status =='Warm' ||
                        //             $lead->lead_status =='Cold' || $lead->lead_status =='RFQ-Transfer' || $lead->lead_status =='RFQ-Follow Up' ||
                        //             $latestActivityLog->description == '4th Salesperson Transfer Follow Up' ||
                        //             $latestActivityLog->description == 'Demo Cancelled. 4th Demo Cancelled Follow Up' ||
                        //             $latestActivityLog->description == '4th Quotation Transfer Follow Up') {
                        //             return false; // Show button
                        //         }
                        //     }

                        //     return true; // Default: Hide button
                        // }),
                    // LeadActions::getConfirmOrderAction()
                    //     ->visible(function (Lead $lead) {
                    //         $latestActivityLog = $lead->activityLogs()->latest()->first();

                    //         if (!$latestActivityLog) {
                    //             return false;
                    //         }

                    //         $description = $latestActivityLog->description;
                    //         $attributes = json_decode($latestActivityLog->properties, true)['attributes'] ?? [];
                    //         $leadStatus = data_get($attributes, 'lead_status');

                    //         return (
                    //             (str_contains($description, 'Quotation Sent.') && $leadStatus !== LeadStatusEnum::PENDING_DEMO->value)
                    //             || str_contains($description, 'Quotation Transfer')
                    //         );
                    //     }),
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getViewRemark(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.pr-today-salesperson-table');
    }
}
