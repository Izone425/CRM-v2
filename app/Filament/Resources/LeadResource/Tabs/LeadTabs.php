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
            Grid::make(4) // A three-column grid for overall layout
            ->schema([
                // Left-side layout
                Grid::make(1) // Nested grid for left side (single column)
                    ->schema([
                        Section::make('Lead Details')
                            ->headerActions([
                                Action::make('edit_person_in_charge')
                                    ->label('Edit') // Button label
                                    ->icon('heroicon-o-pencil')
                                    ->visible(fn (Lead $lead) => auth()->user()->role_id !== 2)
                                    ->modalHeading('Edit Lead Detail') // Modal heading
                                    ->modalSubmitActionLabel('Save Changes') // Modal button text
                                    ->form([ // Define the form fields to show in the modal
                                        Select::make('company_size')
                                            ->label('Company Size')
                                            ->options([
                                                '1-24' => '1-24',
                                                '25-99' => '25-99',
                                                '100-500' => '100-500',
                                                '501 and Above' => '501 and Above',
                                            ])
                                            ->required()
                                            ->default(fn ($record) => $record?->company_size ?? 'Unknown'),
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
                    ])
                    ->columnSpan(1),
                Grid::make(1)
                    ->schema([
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
                    ])->columnSpan(2),
                Grid::make(1)
                    ->schema([
                        Section::make('Sales Progress')
                            ->headerActions([
                                Action::make('edit_utm_details')
                                    ->label('Edit') // Modal buttonF
                                    ->icon('heroicon-o-pencil')
                            ])
                            ->schema([
                                View::make('components.progress')
                                    ->extraAttributes(fn ($record) => ['record' => $record]), // Pass record to view

                                // Add Customer Portal Activation Button
                                Actions::make([
                                    Action::make('send_customer_activation')
                                        ->label('Send Customer Portal Activation')
                                        ->icon('heroicon-o-envelope')
                                        ->color('primary')
                                        ->button()
                                        ->visible(function ($record) {
                                            return false;

                                            // Only show for leads with company details and email
                                            return $record &&
                                                    $record->companyDetail &&
                                                    $record->email &&
                                                    !empty($record->companyDetail->company_name);
                                        })
                                        ->modalHeading('Send Customer Portal Activation Email')
                                        ->modalDescription('This will send an activation email to the customer to set up their portal account.')
                                        ->modalSubmitActionLabel('Send Activation Email')
                                        ->action(function ($record) {
                                            $controller = app(\App\Http\Controllers\CustomerActivationController::class);

                                            try {
                                                $controller->sendActivationEmail($record->id);

                                                Notification::make()
                                                    ->title('Activation Email Sent')
                                                    ->success()
                                                    ->body('The customer portal activation email has been sent to ' . $record->companyDetail->email)
                                                    ->send();

                                                // Log the activity
                                                activity()
                                                    ->causedBy(auth()->user())
                                                    ->performedOn($record)
                                                    ->withProperties([
                                                        'email' => $record->email,
                                                        'name' => $record->companyDetail->name ?? $record->name
                                                    ])
                                                    ->log('Customer portal activation email sent');

                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->danger()
                                                    ->body('Failed to send activation email: ' . $e->getMessage())
                                                    ->send();
                                            }
                                        })
                                    ]),
                                // ->visible(function ($record) {
                                //     // Only show for leads with appropriate status
                                //     return in_array($record->lead_status, ['Pending Demo', 'Demo Scheduled', 'Hot', 'Quotation Sent', 'Closed']);
                                // }),
                            ]),
                    ])
                    ->columnSpan(1),
                // Grid::make(1)
                //     ->schema([
                //         Section::make('Sales In-Charge')
                //             ->extraAttributes([
                //                 'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                //             ])
                //             ->schema([
                //                 Grid::make(1) // Single column in the right-side section
                //                     ->schema([
                //                         View::make('components.lead-owner'),
                //                         Actions::make([
                //                             Actions\Action::make('edit_sales_in_charge')
                //                                 ->label('Edit')
                //                                 ->visible(function ($record) {
                //                                     return (auth()->user()?->role_id === 1 && !is_null($record->lead_owner) && !is_null($record->salesperson)
                //                                     || auth()->user()?->role_id === 3);
                //                                 })
                //                                 ->form(array_merge(
                //                                     auth()->user()->role_id !== 1
                //                                         ? [
                //                                             Grid::make()
                //                                                 ->schema([
                //                                                     Select::make('position')
                //                                                         ->label('Lead Owner Role')
                //                                                         ->options([
                //                                                             'sale_admin' => 'Sales Admin',
                //                                                         ]),
                //                                                     Select::make('lead_owner')
                //                                                         ->label('Lead Owner')
                //                                                         ->default(fn ($record) => $record?->lead_owner ?? null)
                //                                                         ->options(
                //                                                             \App\Models\User::where('role_id', 1)
                //                                                                 ->pluck('name', 'id')
                //                                                         )
                //                                                         ->searchable(),
                //                                                 ])->columns(2),
                //                                         ]
                //                                         : [],
                //                                     [
                //                                         Select::make('salesperson')
                //                                             ->label('Salesperson')
                //                                             ->options(
                //                                                 \App\Models\User::where('role_id', 2)
                //                                                     ->pluck('name', 'id')
                //                                             )
                //                                             ->default(fn ($record) => $record?->salesperson)
                //                                             ->required()
                //                                             ->searchable(),
                //                                     ]
                //                                 ))
                //                                 ->action(function ($record, $data) {
                //                                     if (!empty($data['salesperson'])) {
                //                                         $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';
                //                                         $record->update(['salesperson' => $data['salesperson'],
                //                                             'salesperson_assigned_date' => now(),
                //                                             ]);
                //                                     }

                //                                     // Check and update lead_owner if it's not null
                //                                     if (!empty($data['lead_owner'])) {
                //                                         $leadOwnerName = \App\Models\User::find($data['lead_owner'])?->name ?? 'Unknown Lead Owner';
                //                                         $record->update(['lead_owner' => $leadOwnerName]);
                //                                     }

                //                                     $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                //                                         ->orderByDesc('created_at')
                //                                         ->take(2)
                //                                         ->get();

                //                                     // Check if at least two logs exist
                //                                     if (auth()->user()->role_id == 3) {
                //                                         $causer_id = auth()->user()->id;
                //                                         $causer_name = \App\Models\User::find($causer_id)->name;
                //                                         $latestActivityLogs[0]->update([
                //                                             'description' => 'Lead Owner updated by '. $causer_name . ": " . $leadOwnerName,
                //                                         ]);

                //                                         // Update the second activity log
                //                                         $latestActivityLogs[1]->update([
                //                                             'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                //                                         ]);
                //                                     }else{
                //                                         $causer_id = auth()->user()->id;
                //                                         $causer_name = \App\Models\User::find($causer_id)->name;
                //                                         // $latestActivityLogs[0]->delete();
                //                                         $latestActivityLogs[0]->update([
                //                                             'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                //                                         ]);
                //                                     }
                //                                     // Log the activity for auditing
                //                                     activity()
                //                                         ->causedBy(auth()->user())
                //                                         ->performedOn($record);

                //                                     Notification::make()
                //                                     ->title('Sales In-Charge Edited Successfully')
                //                                     ->success()
                //                                     ->send();
                //                                 })
                //                                 ->modalHeading('Edit Sales In-Charge')
                //                                 ->modalDescription('Changing the Lead Owner and Salesperson will allow the new staff
                //                                                     to take action on the current and future follow-ups only.')
                //                                 ->modalSubmitActionLabel('Save Changes'),

                //                             Actions\Action::make('request_change_lead_owner')
                //                                 ->label('Request Change Lead Owner')
                //                                 ->icon('heroicon-o-paper-airplane')
                //                                 ->visible(fn () => auth()->user()?->role_id == 1) // Only visible to non-manager roles
                //                                 ->form([
                //                                     \Filament\Forms\Components\Select::make('requested_owner_id')
                //                                         ->label('New Lead Owner')
                //                                         ->searchable()
                //                                         ->required()
                //                                         ->options(
                //                                             \App\Models\User::where('role_id', 1)->pluck('name', 'id') // Assuming lead owners are role_id = 1
                //                                         ),
                //                                     \Filament\Forms\Components\Textarea::make('reason')
                //                                         ->label('Reason for Request')
                //                                         ->rows(3)
                //                                         ->autosize()
                //                                         ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                //                                         ->required(),
                //                                 ])
                //                                 ->action(function ($record, array $data) {
                //                                     $manager = \App\Models\User::where('role_id', 3)->first();

                //                                     // Create the request
                //                                     \App\Models\Request::create([
                //                                         'lead_id' => $record->id,
                //                                         'requested_by' => auth()->id(),
                //                                         'current_owner_id' => \App\Models\User::where('name', $record->lead_owner)->value('id'),
                //                                         'requested_owner_id' => $data['requested_owner_id'],
                //                                         'reason' => $data['reason'],
                //                                         'status' => 'pending',
                //                                     ]);

                //                                     activity()
                //                                         ->causedBy(auth()->user())
                //                                         ->performedOn($record)
                //                                         ->withProperties([
                //                                             'lead_id' => $record->id,
                //                                             'requested_by' => auth()->user()->name,
                //                                             'requested_owner_id' => \App\Models\User::find($data['requested_owner_id'])?->name,
                //                                             'reason' => $data['reason'],
                //                                         ])
                //                                         ->log('Requested lead owner change');

                //                                     Notification::make()
                //                                         ->title('Request Submitted')
                //                                         ->body('Your request to change the lead owner has been submitted to the manager.')
                //                                         ->success()
                //                                         ->send();

                //                                     if ($manager) {
                //                                         Notification::make()
                //                                             ->title('New Lead Owner Change Request')
                //                                             ->body(auth()->user()->name . ' requested to change the owner for Lead ID: ' . $record->id);
                //                                     }

                //                                     try {
                //                                         $lead = $record;
                //                                         $viewName = 'emails.change_lead_owner';

                //                                         // Set fixed recipient
                //                                         $recipients = collect([
                //                                             (object)[
                //                                                 'email' => 'faiz@timeteccloud.com', // ✅ Your desired recipient
                //                                                 'name' => 'Faiz'
                //                                             ]
                //                                         ]);

                //                                         foreach ($recipients as $recipient) {
                //                                             $emailContent = [
                //                                                 'leadOwnerName' => $recipient->name ?? 'Unknown Person',
                //                                                 'lead' => [
                //                                                     'lead_code' => 'Website',
                //                                                     'lastName' => $lead->name ?? 'N/A',
                //                                                     'company' => $lead->companyDetail->company_name ?? 'N/A',
                //                                                     'companySize' => $lead->company_size ?? 'N/A',
                //                                                     'phone' => $lead->phone ?? 'N/A',
                //                                                     'email' => $lead->email ?? 'N/A',
                //                                                     'country' => $lead->country ?? 'N/A',
                //                                                     'products' => $lead->products ?? 'N/A',
                //                                                 ],
                //                                                 'remark' => $lead->remark ?? 'No remarks provided',
                //                                                 'formatted_products' => is_array($lead->formatted_products)
                //                                                     ? implode(', ', $lead->formatted_products)
                //                                                     : ($lead->formatted_products ?? 'N/A'),
                //                                             ];

                //                                             Mail::to($recipient->email)
                //                                                 ->send(new \App\Mail\ChangeLeadOwnerNotification($emailContent, $viewName));
                //                                         }
                //                                     } catch (\Exception $e) {
                //                                         Log::error("New Lead Email Error: {$e->getMessage()}");
                //                                     }
                //                                 }),
                //                         ]),
                //                     ]),
                //             ])
                //             ->columnSpan(1), // Right side spans 1 column
                //     ])->columnSpan(1),
            ]),
        ];
    }
}
