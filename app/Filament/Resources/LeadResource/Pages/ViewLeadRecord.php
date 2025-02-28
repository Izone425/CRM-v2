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


class ViewLeadRecord extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function mount($record): void
    {
            $code = str_replace(' ', '+', $record); // Replace spaces with +
            $leadId = Encryptor::decrypt($code); // Decrypt the encrypted record ID
            // dd($leadId);
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
            'Closed' => '#E5E4E2',
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
            // Action::make('whatsappMe')
            //     ->label(__('WhatsApp to Lead'))
            //     ->color('success')
            //     ->size(ActionSize::Large)
            //     ->button()
            //     ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
            //     ->url(function () {
            //         $lead = $this->record;
            //         // $userName = auth()->user()->name; // Get current user's name
            //         $whatsappNumber = $lead->phone; // Ensure the user model has this field

            //         // Prepare WhatsApp message content
            //         // $message = "Hi, {$userName} here";

            //         // // Encode the message for URL
            //         // $encodedMessage = urlencode($message);

            //         // Generate WhatsApp URL
            //         return "https://wa.me/{$whatsappNumber}";
            //     }, true),
            Action::make('updateLeadOwner')
                ->label(__('Assign to Me'))
                ->requiresConfirmation()
                ->modalDescription('')
                ->size(ActionSize::Large)
                ->form(function (Lead $record) {
                    $isDuplicate = Lead::query()
                        ->where('company_name', $record->companyDetail->company_name)
                        ->orWhere('email', $record->email)
                        ->where('id', '!=', $record->id) // Exclude the current lead
                        ->exists();

                    $content = $isDuplicate
                        ? '⚠️⚠️⚠️ Warning: This lead is a duplicate based on company name or email. Do you want to assign this lead to yourself?'
                        : 'Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.';

                    return [
                        Placeholder::make('warning')
                            ->content($content)
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
