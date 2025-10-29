<?php
namespace App\Filament\Resources\LeadResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\LeadResource;
use App\Models\ActivityLog;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Session;

class ViewLeadRecord extends ViewRecord
{
    protected static string $resource = LeadResource::class;
    public $lastRefreshTime;
    public $visibleTabs = [];

    public function getBreadcrumbs(): array
    {
        return [];
    }

    // Add this method to the class to handle refreshing
    public function refreshPage()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        // Refresh all relation managers and components
        $this->dispatch('refresh');

        // Refresh specific relation managers if needed
        $this->dispatch('refresh-activity-logs');
        $this->dispatch('refresh-demo-appointments');
        $this->dispatch('refresh-quotations');
        $this->dispatch('refresh-proforma-invoices');
        $this->dispatch('refresh-software-handovers');
        $this->dispatch('refresh-hardware-handovers');

        // Show notification
        Notification::make()
            ->title('Page refreshed')
            ->success()
            ->send();
    }

    public function mount($record): void
    {
            $code = str_replace(' ', '+', $record); // Replace spaces with +
            $leadId = Encryptor::decrypt($code); // Decrypt the encrypted record ID
            $this->record = $this->getModel()::findOrFail($leadId); // Fetch the lead record

            $this->visibleTabs = Session::get('lead_visible_tabs', $this->getDefaultVisibleTabs());
    }

    private function getDefaultVisibleTabs(): array
    {
        $user = auth()->user();

        if (!$user) {
            return ['lead', 'company'];
        } elseif ($user->role_id === 1) { // Lead Owner
                return ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
        } elseif ($user->role_id === 2) { // Salesperson
            return ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
        } elseif ($user->role_id === 4) { // Implementer
            return ['implementer_software_handover','implementer_hardware_handover','implementer_pic_details',
            'implementer_notes', 'implementer_appointment', 'implementer_follow_up',
            'data_file', 'implementer_service_form', 'other_form', 'ticketing', 'project_plan'];
        } elseif ($user->role_id === 5) { // Implementer
            return ['implementer_software_handover','implementer_hardware_handover','implementer_pic_details',
            'implementer_notes', 'implementer_appointment', 'implementer_follow_up',
            'data_file', 'implementer_service_form', 'other_form', 'ticketing', 'project_plan'];
        } elseif ($user->role_id === 9) { // Technician
            return ['company', 'quotation', 'repair_appointment'];
        } else { // Manager (role_id = 3) or others
            return ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
        }
    }

    public function updateVisibleTabs(array $tabs): void
    {
        $this->visibleTabs = $tabs;
        Session::put('lead_visible_tabs', $tabs);

        // Refresh the page to apply changes
        $this->refreshPage();
    }

    public function isTabVisible(string $tabKey): bool
    {
        return in_array($tabKey, $this->visibleTabs);
    }

    public function getTitle(): HtmlString
    {
        $companyName = $this->record->companyDetail->company_name ?? 'Lead Details';
        $leadStatus = $this->record->lead_status ?? 'Unknown';

        // Define background color for lead_status
        $statusColor = match ($leadStatus) {
            'None' => '#ffe1a5',
            'New' => '#ffe1a5',
            'RFQ-Transfer' => '#ffe1a5',
            'Pending Demo' => '#ffe1a5',
            'Under Review' => '#ffe1a5',
            'Demo Cancelled' => '#ffe1a5',
            'Demo-Assigned' => '#ffffa5',
            'RFQ-Follow Up' => '#431fa1e3',
            'Hot' => '#ff0000a1',
            'Warm' => '#FFA500',
            'Cold' => '#00e7ff',
            'Junk' => '#E5E4E2',
            'On Hold' => '#E5E4E2',
            'Lost' => '#E5E4E2',
            'No Response' => '#E5E4E2',
            'Closed' => '#00ff3e',
            default => '#cccccc',
        };

        // Return the HTML string
        return new HtmlString(
            sprintf(
                '<div style="display: flex; align-items: center; gap: 10px;">
                    <h1 style="margin: 0; font-size: 1.5rem;">%s</h1>
                    <span style="background-color: %s; text-align: -webkit-center; width:160px; border-radius: 25px; font-size: 1.25rem;">
                        %s
                    </span>
                </div>',
                e($companyName),  // Escaped company name
                $statusColor,     // Dynamic background color
                e($leadStatus)    // Escaped lead status
            )
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshPage')
                ->hiddenLabel()
                ->tooltip('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->extraAttributes(['class' => 'refresh-btn'])
                ->action('refreshPage'),

            Action::make('filterTabs')
                ->label('Filter Tabs')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->visible(function () {
                    $user = auth()->user();
                    // Show to managers (role_id 3) OR lead owners (role_id 1) with additional_role 1
                    return $user->role_id === 3 || ($user->role_id === 1 && $user->additional_role === 1);
                })
                ->form(function () {
                    $user = auth()->user();

                    // Define available options based on user role
                    $roleOptions = [];

                    // For role_id 3 (managers) - show all options
                    if ($user->role_id === 3) {
                        $roleOptions = [
                            'manager' => 'Master Admin',
                            'admin_renewal_v1' => 'Admin Renewal v1',
                            'admin_renewal_v2' => 'Admin Renewal v2',
                            'admin_repair' => 'Admin Repair',
                            'implementer' => 'Implementer',
                            'lead_owner' => 'Lead Owner',
                            'salesperson' => 'Salesperson',
                            'technician' => 'Technician',
                        ];
                    }
                    // For role_id 1 with additional_role 1 - show limited options
                    elseif ($user->role_id === 1 && $user->additional_role === 1) {
                        $roleOptions = [
                            'lead_owner' => 'Lead Owner View',
                            'admin_repair' => 'Admin Repair View',
                        ];
                    }

                    return [
                        Select::make('role_view')
                            ->label('Select View')
                            ->options($roleOptions)
                            ->default(function () {
                                $user = auth()->user();

                                if ($user->role_id === 1 && $user->additional_role === 1) {
                                    return 'admin_repair';
                                } elseif ($user->role_id === 1) {
                                    return 'lead_owner';
                                } elseif ($user->role_id === 2) {
                                    return 'salesperson';
                                } elseif ($user->role_id === 3) {
                                    return 'manager';
                                } elseif ($user->role_id === 4) {
                                    return 'implementer';
                                } elseif ($user->role_id === 9) {
                                    return 'technician';
                                }
                            })
                            ->required()
                            ->helperText('Choose which tabs to display based on your role')
                    ];
                })
                ->action(function (array $data) {
                    // Set the visible tabs based on the selected role
                    $tabs = [];
                    $roleView = $data['role_view'] ?? 'lead_owner';

                    switch ($roleView) {
                        case 'lead_owner':
                            $tabs = ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
                            break;
                        case 'implementer':
                            $tabs = ['implementer_software_handover','implementer_hardware_handover','implementer_pic_details',
                                'implementer_notes', 'implementer_appointment', 'implementer_follow_up',
                                'implementer_service_form', 'data_file', 'other_form', 'ticketing', 'project_plan'];
                            break;
                        case 'admin_repair':
                            $tabs = ['company', 'quotation', 'repair_appointment'];
                            break;
                        case 'admin_renewal_v1':
                            $tabs = ['company', 'ar_details','ar_license','ar_quotation','ar_proforma_invoice','ar_follow_up',
                                'ar_notes'];
                            break;
                        case 'admin_renewal_v2':
                            $tabs = ['company', 'ar_details','ar_license','ar_quotation','ar_proforma_invoice','ar_follow_up',
                                'ar_notes'];
                            break;
                        case 'technician':
                            $tabs = ['company', 'quotation', 'repair_appointment'];
                            break;
                        case 'salesperson':
                            $tabs = ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
                            break;
                        case 'manager':
                        default:
                            $tabs = ['prospect_details', 'subscriber_details', 'sales_progress', 'commercial_items', 'handover_details'];
                            break;
                    }

                    $this->updateVisibleTabs($tabs);

                    Notification::make()
                        ->title('Tab visibility updated')
                        ->success()
                        ->send();
                }),
            Action::make('updateLeadOwner')
                ->label(__('Assign to Me'))
                ->requiresConfirmation()
                ->modalDescription('')
                ->size(ActionSize::Large)
                ->form(function (Lead $record) {
                    $duplicateLeads = Lead::query()
                        ->where(function ($query) use ($record) {
                            if (optional($record?->companyDetail)->company_name) {
                                $query->where('company_name', $record->companyDetail->company_name);
                            }

                            if (!empty($record?->email)) {
                                $query->orWhere('email', $record->email);
                            }

                            if (!empty($record?->phone)) {
                                $query->orWhere('phone', $record->phone);
                            }
                        })
                        ->where('id', '!=', optional($record)->id)
                        ->where(function ($query) {
                            $query->whereNull('company_name')
                                ->orWhereRaw("company_name NOT LIKE '%SDN BHD%'")
                                ->orWhereRaw("company_name NOT LIKE '%SDN. BHD.%'");
                        })
                        ->get(['id']);

                    $isDuplicate = $duplicateLeads->isNotEmpty();

                    $duplicateIds = $duplicateLeads->map(fn ($lead) => "LEAD ID " . str_pad($lead->id, 5, '0', STR_PAD_LEFT))
                        ->implode("\n\n");

                    $content = $isDuplicate
                        ? "⚠️⚠️⚠️ Warning: This lead is a duplicate based on company name, email, or phone. Do you want to assign this lead to yourself?\n\n$duplicateIds"
                        : "Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.";

                    return [
                        Placeholder::make('warning')
                            ->content(Str::of($content)->replace("\n", '<br>')->toHtmlString())
                            ->hiddenLabel()
                            ->extraAttributes([
                                'style' => $isDuplicate ? 'color: red; font-weight: bold;' : '',
                            ]),
                    ];
                })
                ->color('success')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (Lead $record) => is_null($record->lead_owner) && auth()->user()->role_id !== 2
                && is_null($record->salesperson))
                ->action(function (Lead $record) {
                    // Update the lead owner and related fields
                    $record->update([
                        'lead_owner' => auth()->user()->name,
                        'categories' => 'Active',
                        'stage' => 'Transfer',
                        'lead_status' => 'New',
                        'pickup_date' => now(),
                    ]);

                    // Update the latest activity log
                    $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                        $latestActivityLog->update([
                            'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                        ]);

                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record);
                    }

                    Notification::make()
                        ->title('Lead Owner Assigned Successfully')
                        ->success()
                        ->send();
                })
        ];
    }
}
