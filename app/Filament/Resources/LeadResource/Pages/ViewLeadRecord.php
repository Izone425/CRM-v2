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


class ViewLeadRecord extends ViewRecord
{
    protected static string $resource = LeadResource::class;
    public $lastRefreshTime;

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
