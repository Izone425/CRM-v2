<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class LeadTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Lead Details')
                ->headerActions([
                    Action::make('edit_person_in_charge')
                        ->label('Edit') // Button label
                        ->icon('heroicon-o-pencil')
                        ->visible(fn (Lead $lead) => auth()->user()->role_id !== 2)
                        ->modalHeading('Edit Lead Detail') // Modal heading
                        ->modalSubmitActionLabel('Save Changes') // Modal button text
                        ->form([ // Define the form fields to show in the modal
                            // Select::make('lead_code')
                            //     ->label('Lead Source')
                            //     ->options(function () {
                            //         $user = auth()->user();

                            //         // Get all lead sources
                            //         $query = LeadSource::query();

                            //         // Apply role-based filtering
                            //         if ($user->role_id === 1) { // Lead Owner
                            //             $query->where('accessible_by_lead_owners', true);
                            //         } elseif ($user->role_id === 2) { // Salesperson
                            //             if ($user->is_timetec_hr) {
                            //                 $query->where('accessible_by_timetec_hr_salespeople', true);
                            //             } else {
                            //                 $query->where('accessible_by_non_timetec_hr_salespeople', true);
                            //             }
                            //         }
                            //         // Managers (role_id 3) can see all options

                            //         return $query->pluck('lead_code', 'lead_code');
                            //     })
                            //     ->required(),
                            // Select::make('lead_code')
                            //     ->label('Lead Source')
                            //     ->default(function () {
                            //         $roleId = Auth::user()->role_id;
                            //         return $roleId == 2 ? 'Salesperson Lead' : ($roleId == 1 ? 'Website' : '');
                            //     })
                            //     ->options(fn () => LeadSource::pluck('lead_code', 'lead_code')->toArray())
                            //     ->searchable()
                            //     ->required(),

                            Select::make('lead_code')
                                ->label('Lead Source')
                                // ->default(function () {
                                //     $roleId = Auth::user()->role_id;
                                //     return $roleId == 2 ? 'Salesperson Lead' : ($roleId == 1 ? 'Website' : '');
                                // })
                                ->options(function () {
                                    $user = Auth::user();

                                    // For other users, get only the lead sources they have access to
                                    $leadSources = LeadSource::all();

                                    $accessibleLeadSources = $leadSources->filter(function($leadSource) use ($user) {
                                        // If allowed_users is not set or empty, everyone can access
                                        if (empty($leadSource->allowed_users)) {
                                            return false;  // Change to true if you want unassigned lead sources to be available to everyone
                                        }

                                        // Check if user ID is in the allowed_users array
                                        $allowedUsers = is_array($leadSource->allowed_users)
                                            ? $leadSource->allowed_users
                                            : json_decode($leadSource->allowed_users, true);

                                        return in_array($user->id, $allowedUsers);
                                    });

                                    return $accessibleLeadSources->pluck('lead_code', 'lead_code')->toArray();
                                })
                                ->searchable()
                                ->required(),

                            Select::make('customer_type')
                                ->label('Customer Type')
                                ->options([
                                    'END USER' => 'END USER',
                                    'RESELLER' => 'RESELLER',
                                ])
                                ->required()
                                ->default(fn ($record) => $record?->customer_type ?? 'Unknown')
                                ->visible(fn () => auth()->user()?->role_id == 3),
                            Select::make('region')
                                ->label('Region')
                                ->options([
                                    'LOCAL' => 'LOCAL',
                                    'OVERSEA' => 'OVERSEA',
                                ])
                                ->required()
                                ->default(fn ($record) => $record?->region ?? 'Unknown')
                                ->visible(fn () => auth()->user()?->role_id == 3),
                        ])
                        ->action(function (Lead $lead, array $data) {
                            if ($lead) {
                                // Update the existing SystemQuestion record
                                $lead->updateQuietly($data);

                                Notification::make()
                                    ->title('Updated Successfully')
                                    ->success()
                                    ->send();
                            }
                        }),
                ])
                ->schema([
                    View::make('components.lead-detail'),
                ]),
            Section::make('UTM Details')
                ->headerActions([
                    Action::make('edit_utm_details')
                        ->label('Edit') // Modal buttonF
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Edit UTM Details')
                        ->modalSubmitActionLabel('Save Changes')
                        ->visible(fn (Lead $lead) => auth()->user()->role_id !== 2)
                        ->form([
                            TextInput::make('utm_campaign')
                                ->label('UTM Campaign')
                                ->default(fn ($record) => $record->utmDetail->utm_campaign ?? ''),

                            TextInput::make('utm_adgroup')
                                ->label('UTM Adgroup')
                                ->default(fn ($record) => $record->utmDetail->utm_adgroup ?? ''),

                            TextInput::make('referrername')
                                ->label('Referrer Name')
                                ->default(fn ($record) => $record->utmDetail->referrername ?? ''),

                            TextInput::make('utm_creative')
                                ->label('UTM Creative')
                                ->default(fn ($record) => $record->utmDetail->utm_creative ?? ''),

                            TextInput::make('utm_term')
                                ->label('UTM Term')
                                ->default(fn ($record) => $record->utmDetail->utm_term ?? ''),

                            TextInput::make('utm_matchtype')
                                ->label('UTM Match Type')
                                ->default(fn ($record) => $record->utmDetail->utm_matchtype ?? ''),

                            TextInput::make('device')
                                ->label('Device')
                                ->default(fn ($record) => $record->utmDetail->device ?? ''),

                            TextInput::make('gclid')
                                ->label('GCLID')
                                ->default(fn ($record) => $record->utmDetail->gclid ?? ''),

                            TextInput::make('social_lead_id')
                                ->label('Social Lead ID')
                                ->default(fn ($record) => $record->utmDetail->social_lead_id ?? ''),
                        ])
                        ->action(function (Lead $lead, array $data) {
                            $utm = $lead->utmDetail;

                            if (!$utm) {
                                $utm = $lead->utmDetail()->create($data); // create new if not exists
                            } else {
                                $utm->update($data);
                            }

                            Notification::make()
                                ->title('UTM Details Updated')
                                ->success()
                                ->send();
                        }),
                ])
                ->schema([
                    View::make('components.utm-details')
                        ->extraAttributes(fn ($record) => ['record' => $record]),
                ]),
    // Grid::make(1)
    //     ->schema([
    //         Section::make('Sales Progress')
    //             ->headerActions([
    //                 Action::make('edit_utm_details')
    //                     ->label('Edit') // Modal buttonF
    //                     ->icon('heroicon-o-pencil')
    //             ])
    //             ->schema([
    //                 View::make('components.progress')
    //                     ->extraAttributes(fn ($record) => ['record' => $record]), // Pass record to view

    //                 // Add Customer Portal Activation Button
    //                 Actions::make([
    //                     Action::make('send_customer_activation')
    //                         ->label('Send Customer Portal Activation')
    //                         ->icon('heroicon-o-envelope')
    //                         ->color('primary')
    //                         ->button()
    //                         ->visible(function ($record) {
    //                             return false;

    //                             // Only show for leads with company details and email
    //                             return $record &&
    //                                     $record->companyDetail &&
    //                                     $record->email &&
    //                                     !empty($record->companyDetail->company_name);
    //                         })
    //                         ->modalHeading('Send Customer Portal Activation Email')
    //                         ->modalDescription('This will send an activation email to the customer to set up their portal account.')
    //                         ->modalSubmitActionLabel('Send Activation Email')
    //                         ->action(function ($record) {
    //                             $controller = app(\App\Http\Controllers\CustomerActivationController::class);

    //                             try {
    //                                 $controller->sendActivationEmail($record->id);

    //                                 Notification::make()
    //                                     ->title('Activation Email Sent')
    //                                     ->success()
    //                                     ->body('The customer portal activation email has been sent to ' . $record->companyDetail->email)
    //                                     ->send();

    //                                 // Log the activity
    //                                 activity()
    //                                     ->causedBy(auth()->user())
    //                                     ->performedOn($record)
    //                                     ->withProperties([
    //                                         'email' => $record->email,
    //                                         'name' => $record->companyDetail->name ?? $record->name
    //                                     ])
    //                                     ->log('Customer portal activation email sent');

    //                             } catch (\Exception $e) {
    //                                 Notification::make()
    //                                     ->title('Error')
    //                                     ->danger()
    //                                     ->body('Failed to send activation email: ' . $e->getMessage())
    //                                     ->send();
    //                             }
    //                         })
    //                     ]),
    //                 // ->visible(function ($record) {
    //                 //     // Only show for leads with appropriate status
    //                 //     return in_array($record->lead_status, ['Pending Demo', 'Demo Scheduled', 'Hot', 'Quotation Sent', 'Closed']);
    //                 // }),
    //             ]),
    //     ])
    //     ->columnSpan(1),
        ];
    }
}
