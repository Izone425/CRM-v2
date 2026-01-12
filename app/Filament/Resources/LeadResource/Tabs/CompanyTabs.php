<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Mail\BDReferralClosure;
use App\Models\ActivityLog;
use App\Models\Industry;
use App\Models\InvalidLeadReason;
use App\Models\Lead;
use App\Models\LeadSource;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CompanyTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(4)
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Section::make('Company Details')
                                ->headerActions([
                                    Action::make('edit_company_detail')
                                        ->label('Edit') // Button label
                                        ->icon('heroicon-o-pencil')
                                        ->modalHeading('Edit Information') // Modal heading
                                        ->visible(fn (Lead $lead) =>
                                            // First check if user role is not 4 or 5
                                            in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                            // If user is role 2 (salesperson), they can only edit their own leads
                                            (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                            // Then check if lead owner exists or salesperson exists
                                            (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                        )
                                        ->modalSubmitActionLabel('Save Changes') // Modal button text
                                        ->form(function (Lead $record) {
                                            // Check if the lead was created more than 30 days ago
                                            $isOlderThan30Days = $record->created_at->diffInDays(now()) > 30;
                                            $isAdmin = auth()->user()->role_id === 1;

                                            $schema = [];

                                            // Add company_name field with appropriate disabled state
                                            $schema[] = TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->default(strtoupper($record->companyDetail->company_name ?? '-'))
                                                ->disabled(function () use ($isOlderThan30Days, $isAdmin, $record) {
                                                    // Rule 1: If user has role_id 3, never disable the field regardless of lead age
                                                    if (auth()->user()->role_id === 3) {
                                                        return false;
                                                    }

                                                    // Rule 2: If lead has a salesperson assigned and current user is role_id 1, disable the field
                                                    if (!is_null($record->salesperson) && auth()->user()->role_id === 1) {
                                                        return true;
                                                    }

                                                    // Rule 3: Original condition - disable if older than 30 days and not admin
                                                    return $isOlderThan30Days && !$isAdmin;
                                                })
                                                ->helperText(function () use ($isOlderThan30Days, $isAdmin, $record) {
                                                    // If user has role_id 3, no helper text needed
                                                    if (auth()->user()->role_id === 3) {
                                                        return '';
                                                    }

                                                    // If lead has a salesperson assigned and current user is role_id 1
                                                    if (!is_null($record->salesperson) && auth()->user()->role_id === 1) {
                                                        return 'Company name cannot be edited when a salesperson is assigned.';
                                                    }

                                                    // Original helper text
                                                    return $isOlderThan30Days && !$isAdmin ?
                                                        'Company name cannot be changed after 30 days. Please ask for Faiz on this issue.' : '';
                                                })
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->rules([
                                                    'regex:/^[A-Z0-9\s()]+$/i',
                                                ])
                                                ->validationMessages([
                                                    'regex' => 'Company name can only contain letters, numbers, spaces, and brackets ().',
                                                ]);

                                            $schema[] = Grid::make(2)
                                                ->schema([
                                                    Select::make('company_size')
                                                        ->label('Company Size')
                                                        ->options([
                                                            '1-24' => '1-24',
                                                            '25-99' => '25-99',
                                                            '100-500' => '100-500',
                                                            '501 and Above' => '501 and Above',
                                                        ])
                                                        ->required()
                                                        ->visible(fn () => in_array(auth()->user()->role_id, [1, 3]))
                                                        ->default(fn ($record) => $record->company_size ?? 'Unknown'),                                                 ]);

                                            return $schema;
                                        })
                                        ->action(function (Lead $lead, array $data) {
                                            $isOlderThan30Days = $lead->created_at->diffInDays(now()) > 30;
                                            $isAdmin = auth()->user()->role_id === 1;
                                            $isSpecialRole = auth()->user()->role_id === 3;

                                            // If trying to update company name and it's older than 30 days but not admin or special role
                                            if (isset($data['company_name']) && $isOlderThan30Days && !$isAdmin && !$isSpecialRole) {
                                                // Remove company_name from the data if user shouldn't be able to update it
                                                $originalCompanyName = $lead->companyDetail->company_name ?? '-';

                                                // If they somehow attempted to change the value despite the disabled field
                                                if ($data['company_name'] !== $originalCompanyName) {
                                                    Notification::make()
                                                        ->title('Permission Denied')
                                                        ->danger()
                                                        ->body('You are not authorized to change the company name after 30 days.')
                                                        ->send();

                                                    return;
                                                }
                                            }

                                            // Extract company_size for the lead table
                                            $companySize = $data['company_size'] ?? null;
                                            unset($data['company_size']); // Remove from data array for CompanyDetail

                                            // Update the Lead table with company_size
                                            if ($companySize) {
                                                $lead->update(['company_size' => $companySize]);
                                            }

                                            // Handle CompanyDetail update/create
                                            $record = $lead->companyDetail;
                                            if ($record) {
                                                // Update the existing CompanyDetail record
                                                $record->update($data);

                                                // Also update e_invoice_details if company_name was changed
                                                if (isset($data['company_name']) && $lead->eInvoiceDetail) {
                                                    $lead->eInvoiceDetail->update([
                                                        'company_name' => $data['company_name']
                                                    ]);
                                                }

                                                Notification::make()
                                                    ->title('Updated Successfully')
                                                    ->success()
                                                    ->send();

                                                // Log if admin or special role changed company name on an old record
                                                if ($isOlderThan30Days && ($isAdmin || $isSpecialRole) &&
                                                    isset($data['company_name']) &&
                                                    $data['company_name'] !== $record->getOriginal('company_name')) {

                                                    activity()
                                                        ->causedBy(auth()->user())
                                                        ->performedOn($lead)
                                                        ->log(($isSpecialRole ? 'Special role' : 'Admin') . ' modified company name on a lead older than 30 days');
                                                }
                                            } else {
                                                // Create a new CompanyDetail record via the relation
                                                $lead->companyDetail()->create($data);

                                                Notification::make()
                                                    ->title('Created Successfully')
                                                    ->success()
                                                    ->send();
                                            }
                                        })
                                ])
                                ->schema([
                                    View::make('components.company-detail')
                                ]),
                            Section::make('HR Details')
                                ->headerActions([
                                    Action::make('edit_person_in_charge')
                                        ->label('Edit') // Button label
                                        ->icon('heroicon-o-pencil')
                                        ->visible(fn (Lead $lead) =>
                                            // First check if user role is not 4 or 5
                                            in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                            // If user is role 2 (salesperson), they can only edit their own leads
                                            (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                            // Then check if lead owner exists or salesperson exists
                                            (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                        )
                                        ->modalHeading('Edit HR Details') // Modal heading
                                        ->modalSubmitActionLabel('Save Changes') // Modal button text
                                        ->form([ // Define the form fields to show in the modal
                                            TextInput::make('name')
                                                ->label('Name')
                                                ->required()
                                                ->default(fn ($record) => $record->companyDetail->name ?? $record->name)
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->afterStateUpdated(fn ($state, callable $set) => $set('name', strtoupper($state))),
                                            TextInput::make('email')
                                                ->label('Email')
                                                ->required()
                                                ->default(fn ($record) => $record->companyDetail->email ?? $record->email),
                                            TextInput::make('contact_no')
                                                ->label('Contact No.')
                                                ->required()
                                                ->default(fn ($record) => $record->companyDetail->contact_no ?? $record->phone),
                                            TextInput::make('position')
                                                ->label('Position')
                                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                                ->required()
                                                ->default(fn ($record) => $record->companyDetail->position ?? '-'),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            $record = $lead->companyDetail;
                                            if ($record) {
                                                // Update the existing SystemQuestion record
                                                $record->update($data);

                                                Notification::make()
                                                    ->title('Updated Successfully')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                // Create a new SystemQuestion record via the relation
                                                $lead->bankDetail()->create($data);

                                                Notification::make()
                                                    ->title('Created Successfully')
                                                    ->success()
                                                    ->send();
                                            }
                                        }),
                                ])
                                ->schema([
                                    View::make('components.person-in-charge')
                                ]),
                        ])->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            Section::make('Sales In-Charge')
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ])
                                ->headerActions([
                                    Action::make('edit_sales_in_charge')
                                        ->label('Edit')
                                        ->icon('heroicon-o-pencil')
                                        ->visible(function ($record) {
                                            return (auth()->user()?->role_id === 1 && !is_null($record->lead_owner) && !is_null($record->salesperson)
                                            || auth()->user()?->role_id === 3);
                                        })
                                        ->form(array_merge(
                                            auth()->user()->role_id !== 1
                                                ? [
                                                    Grid::make()
                                                        ->schema([
                                                            Select::make('position')
                                                                ->label('Lead Owner Role')
                                                                ->options([
                                                                    'sale_admin' => 'Sales Admin',
                                                                ]),
                                                            Select::make('lead_owner')
                                                                ->label('Lead Owner')
                                                                ->default(fn ($record) => $record?->lead_owner ?? null)
                                                                ->options(
                                                                    \App\Models\User::where('role_id', 1)
                                                                        ->orWhereIn('id', [4, 5, 52])
                                                                        ->pluck('name', 'name')
                                                                )
                                                                ->searchable(),
                                                        ])->columns(2),
                                                ]
                                                : [],
                                            [
                                                Select::make('salesperson')
                                                    ->label('Salesperson')
                                                    ->options(
                                                        \App\Models\User::where('role_id', 2)
                                                            ->pluck('name', 'id')
                                                    )
                                                    ->default(fn ($record) => $record?->salesperson)
                                                    ->searchable(),
                                            ]
                                        ))
                                        ->action(function ($record, $data) {
                                            // Initialize variables
                                            $salespersonName = null;
                                            $leadOwnerName = null;

                                            // Check and update salesperson if it's not null
                                            if (!empty($data['salesperson'])) {
                                                $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';
                                                $record->update([
                                                    'salesperson' => $data['salesperson'],
                                                    'salesperson_assigned_date' => now(),
                                                ]);
                                            }

                                            // Check and update lead_owner if it's not null
                                            if (!empty($data['lead_owner'])) {
                                                $leadOwnerName = \App\Models\User::find($data['lead_owner'])?->name ?? 'Unknown Lead Owner';
                                                $record->update(['lead_owner' => $data['lead_owner']]); // ✅ Store ID
                                            }

                                            $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                                                ->orderByDesc('created_at')
                                                ->take(2)
                                                ->get();

                                            // Check if at least two logs exist
                                            if (auth()->user()->role_id == 3) {
                                                $causer_id = auth()->user()->id;
                                                $causer_name = \App\Models\User::find($causer_id)->name;

                                                if (isset($latestActivityLogs[0]) && $leadOwnerName) {
                                                    $latestActivityLogs[0]->update([
                                                        'description' => 'Lead Owner updated by '. $causer_name . ": " . $leadOwnerName,
                                                    ]);
                                                }

                                                // Update the second activity log
                                                if (isset($latestActivityLogs[1]) && $salespersonName) {
                                                    $latestActivityLogs[1]->update([
                                                        'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                                                    ]);
                                                }
                                            } else {
                                                $causer_id = auth()->user()->id;
                                                $causer_name = \App\Models\User::find($causer_id)->name;

                                                if (isset($latestActivityLogs[0]) && $salespersonName) {
                                                    $latestActivityLogs[0]->update([
                                                        'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                                                    ]);
                                                }
                                            }

                                            // Log the activity for auditing
                                            activity()
                                                ->causedBy(auth()->user())
                                                ->performedOn($record)
                                                ->log('Sales in-charge updated');

                                            Notification::make()
                                                ->title('Sales In-Charge Edited Successfully')
                                                ->success()
                                                ->send();
                                        })
                                        ->modalHeading('Edit Sales In-Charge')
                                        ->modalDescription('Changing the Lead Owner and Salesperson will allow the new staff
                                                            to take action on the current and future follow-ups only.')
                                        ->modalSubmitActionLabel('Save Changes'),

                                    Action::make('request_change_lead_owner')
                                        ->label('Request Change Lead Owner')
                                        ->visible(fn () => auth()->user()?->role_id == 1) // Only visible to non-manager roles
                                        ->form([
                                            \Filament\Forms\Components\Select::make('requested_owner_id')
                                                ->label('New Lead Owner')
                                                ->searchable()
                                                ->required()
                                                ->options(
                                                    \App\Models\User::where('role_id', 1)->pluck('name', 'id') // Assuming lead owners are role_id = 1
                                                ),
                                            \Filament\Forms\Components\Textarea::make('reason')
                                                ->label('Reason for Request')
                                                ->rows(3)
                                                ->autosize()
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                ->required(),
                                        ])
                                        ->action(function ($record, array $data) {
                                            $manager = \App\Models\User::where('role_id', 3)->first();

                                            // Create the request
                                            \App\Models\Request::create([
                                                'lead_id' => $record->id,
                                                'requested_by' => auth()->id(),
                                                'current_owner_id' => \App\Models\User::where('name', $record->lead_owner)->value('id'),
                                                'requested_owner_id' => $data['requested_owner_id'],
                                                'reason' => $data['reason'],
                                                'status' => 'pending',
                                            ]);

                                            activity()
                                                ->causedBy(auth()->user())
                                                ->performedOn($record)
                                                ->withProperties([
                                                    'lead_id' => $record->id,
                                                    'requested_by' => auth()->user()->name,
                                                    'requested_owner_id' => \App\Models\User::find($data['requested_owner_id'])?->name,
                                                    'reason' => $data['reason'],
                                                ])
                                                ->log('Requested lead owner change');

                                            Notification::make()
                                                ->title('Request Submitted')
                                                ->body('Your request to change the lead owner has been submitted to the manager.')
                                                ->success()
                                                ->send();

                                            if ($manager) {
                                                Notification::make()
                                                    ->title('New Lead Owner Change Request')
                                                    ->body(auth()->user()->name . ' requested to change the owner for Lead ID: ' . $record->id);
                                            }

                                            try {
                                                $lead = $record;
                                                $viewName = 'emails.change_lead_owner';

                                                // Set fixed recipient
                                                $recipients = collect([
                                                    (object)[
                                                        'email' => 'faiz@timeteccloud.com', // ✅ Your desired recipient
                                                        'name' => 'Faiz'
                                                    ]
                                                ]);

                                                foreach ($recipients as $recipient) {
                                                    $emailContent = [
                                                        'leadOwnerName' => $recipient->name ?? 'Unknown Person',
                                                        'lead' => [
                                                            'lead_code' => 'Website',
                                                            'lastName' => $lead->name ?? 'N/A',
                                                            'company' => $lead->companyDetail->company_name ?? 'N/A',
                                                            'companySize' => $lead->company_size ?? 'N/A',
                                                            'phone' => $lead->phone ?? 'N/A',
                                                            'email' => $lead->email ?? 'N/A',
                                                            'country' => $lead->country ?? 'N/A',
                                                            'products' => $lead->products ?? 'N/A',
                                                        ],
                                                        'remark' => $lead->remark ?? 'No remarks provided',
                                                        'formatted_products' => is_array($lead->formatted_products)
                                                            ? implode(', ', $lead->formatted_products)
                                                            : ($lead->formatted_products ?? 'N/A'),
                                                    ];

                                                    Mail::to($recipient->email)
                                                        ->send(new \App\Mail\ChangeLeadOwnerNotification($emailContent, $viewName));
                                                }
                                            } catch (\Exception $e) {
                                                Log::error("New Lead Email Error: {$e->getMessage()}");
                                            }
                                        }),
                                ])
                                ->schema([
                                    Grid::make(1) // Single column in the right-side section
                                        ->schema([
                                            View::make('components.lead-owner'),
                                        ]),
                                ])
                                ->columnSpan(1), // Right side spans 1 column
                            Section::make('Status')
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ])
                                ->headerActions([
                                    Action::make('archive')
                                        ->label(__('Edit'))
                                        ->icon('heroicon-o-pencil')
                                        ->visible(fn (Lead $lead) =>
                                            // First check if user role is not 4 or 5
                                            in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                            // If user is role 2 (salesperson), they can only edit their own leads
                                            (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                            // Then check if lead owner exists or salesperson exists
                                            (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                        )
                                        ->modalHeading('Mark Lead as Inactive')
                                        ->form([
                                            Placeholder::make('')
                                                ->content(__('Please select the reason to mark this lead as inactive and add any relevant remarks.')),

                                            Select::make('status')
                                                ->label('INACTIVE STATUS')
                                                ->options(function () {
                                                    // Create base options array
                                                    $options = [
                                                        'On Hold' => 'On Hold',
                                                        'Lost' => 'Lost',
                                                        'Closed' => 'Closed',
                                                    ];

                                                    // Only add Junk option if user is not a salesperson
                                                    if (auth()->user()->role_id != 2) {
                                                        $options['Junk'] = 'Junk';
                                                    }

                                                    return $options;
                                                })
                                                ->default('On Hold')
                                                ->required()
                                                ->reactive(),

                                            Checkbox::make('visible_in_repairs')
                                                ->label('Visible in Repair Dashboard')
                                                ->helperText('When checked, this lead will appear in the Admin Repair Dashboard')
                                                ->default(fn (Lead $record) => $record->visible_in_repairs ?? false)
                                                ->hidden(function (callable $get) {
                                                    // Hide if user is a salesperson (role_id 2)
                                                    if (auth()->user()->role_id == 2) {
                                                        return true;
                                                    }

                                                    // Also hide if status is not 'Closed'
                                                    return $get('status') !== 'Closed';
                                                }),

                                            Select::make('software_handover_id')
                                                ->label('Link Software Handover')
                                                ->options(function (Lead $record) {
                                                    $companyName = $record->companyDetail?->company_name;

                                                    if (!$companyName) {
                                                        return [];
                                                    }

                                                    // Find orphaned software handovers with matching company name
                                                    return \App\Models\SoftwareHandover::whereNull('lead_id')
                                                        ->where('company_name', 'LIKE', "%{$companyName}%")
                                                        ->get()
                                                        ->mapWithKeys(function ($handover) {
                                                            $date = $handover->created_at->format('d M Y');
                                                            $implementer = $handover->implementer ?? 'Unknown';
                                                            return [$handover->id => "#{$handover->id} - {$handover->company_name} ({$implementer} - {$date})"];
                                                        })
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->placeholder('Select handover to link')
                                                ->helperText('Link an orphaned software handover to this lead')
                                                ->hidden(function (callable $get) {
                                                    // Hide if user is a salesperson (role_id 2)
                                                    if (auth()->user()->role_id == 2) {
                                                        return true;
                                                    }

                                                    // No need to hide based on status
                                                    return false;
                                                }),

                                            // Reason Field - Visible only when status is NOT Closed
                                            Select::make('reason')
                                                ->label('Select a Reason')
                                                ->options(fn (callable $get) =>
                                                    $get('status') !== 'Closed'
                                                        ? InvalidLeadReason::where('lead_stage', $get('status'))->pluck('reason', 'id')->toArray()
                                                        : [] // Hide options when Closed
                                                )
                                                ->hidden(fn (callable $get) => $get('status') === 'Closed')
                                                ->required(fn (callable $get) => $get('status') !== 'Closed')
                                                ->reactive()
                                                ->createOptionForm([
                                                    Select::make('lead_stage')
                                                        ->options([
                                                            'On Hold' => 'On Hold',
                                                            'Junk' => 'Junk',
                                                            'Lost' => 'Lost',
                                                            'Closed' => 'Closed',
                                                        ])
                                                        ->default(fn (callable $get) => $get('status'))
                                                        ->required(),
                                                    TextInput::make('reason')
                                                        ->label('New Reason')
                                                        ->required(),
                                                ])
                                                ->createOptionUsing(function (array $data) {
                                                    $newReason = InvalidLeadReason::create([
                                                        'lead_stage' => $data['lead_stage'],
                                                        'reason' => $data['reason'],
                                                    ]);
                                                    return $newReason->id;
                                                }),

                                            Textarea::make('remark')
                                                ->label('Remarks')
                                                ->rows(3)
                                                ->autosize()
                                                ->reactive()
                                                ->required()
                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                                        ])
                                        ->action(function (Lead $record, array $data) {
                                            $statusLabels = [
                                                'on_hold' => 'On Hold',
                                                'junk' => 'Junk',
                                                'lost' => 'Lost',
                                                'closed' => 'Closed',
                                            ];

                                            $statusLabel = $statusLabels[$data['status']] ?? $data['status'];
                                            $lead = $record;

                                            $updateData = [
                                                'categories' => 'Inactive',
                                                'lead_status' => $statusLabel,
                                                'remark' => $data['remark'],
                                                'stage' => null,
                                                'follow_up_date' => null,
                                                'follow_up_needed' => false,
                                                'visible_in_repairs' => $data['visible_in_repairs'] ?? false,
                                            ];

                                            // If lead is closed, update deal amount
                                            if ($data['status'] === 'Closed') {
                                                $updateData['deal_amount'] = $data['deal_amount'] ?? null;
                                                $updateData['closing_date'] = now();
                                            } else {
                                                // If not closed, update reason
                                                $updateData['reason'] = InvalidLeadReason::find($data['reason'])?->reason ?? 'Unknown Reason';
                                            }

                                            $lead->update($updateData);

                                            if (!empty($data['software_handover_id'])) {
                                                $handoverId = $data['software_handover_id'];
                                                $handover = \App\Models\SoftwareHandover::find($handoverId);

                                                if ($handover) {
                                                    // Update the software handover with the lead_id
                                                    $handover->update([
                                                        'lead_id' => $lead->id
                                                    ]);

                                                    // Log this action
                                                    activity()
                                                        ->causedBy(auth()->user())
                                                        ->performedOn($lead)
                                                        ->log('Software handover #' . $handoverId . ' linked to this lead');
                                                }
                                            }

                                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                                ->orderByDesc('created_at')
                                                ->first();

                                            if ($latestActivityLog) {
                                                activity()
                                                    ->causedBy(auth()->user())
                                                    ->performedOn($lead)
                                                    ->log('Lead marked as inactive.');

                                                sleep(1);

                                                $latestActivityLog->update([
                                                    'description' => 'Marked as ' . $statusLabel . ': ' . ($updateData['reason'] ?? 'Close Deal'),
                                                ]);
                                            }

                                            Notification::make()
                                                ->title('Lead Archived')
                                                ->success()
                                                ->body('You have successfully marked the lead as inactive.')
                                                ->send();
                                        }),
                                ])
                                ->schema([
                                    Grid::make(1) // Single column in the right-side section
                                        ->schema([
                                            View::make('components.deal-information'),
                                        ]),
                                    ]),
                        ])->columnSpan(1),
                    Grid::make(1)
                        ->schema([
                            Section::make('Project Information')
                                ->headerActions([
                                    Action::make('edit_utm_details')
                                        ->label('Edit') // Modal buttonF
                                        ->icon('heroicon-o-pencil')
                                        ->visible(function ($record) {
                                            return (auth()->user()?->role_id === 1 && !is_null($record->lead_owner) && !is_null($record->salesperson)
                                            || auth()->user()?->role_id === 3);
                                        })
                                ])
                                ->extraAttributes([
                                    'style' => 'background-color: #fff5f5; border: dashed; border-color: #feb2b2;'
                                ])
                                // ->visible(function (Lead $lead) {
                                //     // Admin role always has access
                                //     if (auth()->user()->role_id === 3 || auth()->user()->role_id === 5) {
                                //         return true;
                                //     }

                                //     // Check if current user is the implementer for this lead
                                //     $latestHandover = $lead->softwareHandover()
                                //         ->orderBy('created_at', 'desc')
                                //         ->first();

                                //     if ($latestHandover && strtolower($latestHandover->implementer) === strtolower(auth()->user()->name)) {
                                //         return true;
                                //     }

                                //     return false;
                                // })
                                // ->headerActions([
                                //     Action::make('edit_project_info')
                                //         ->label('Edit')
                                //         ->visible(false)
                                //         ->modalHeading('Edit Project Information')
                                //         ->modalSubmitActionLabel('Save Changes')
                                //         ->form([
                                //             Select::make('status_handover')
                                //                 ->label('Project Status')
                                //                 ->options([
                                //                     'InActive' => 'InActive',
                                //                     'Closed' => 'Closed',
                                //                 ])
                                //                 ->default(fn ($record) => $record->softwareHandover()->latest('created_at')->first()?->status_handover ?? 'Open')
                                //                 ->reactive()
                                //                 ->required(),

                                //             DatePicker::make('go_live_date')
                                //                 ->label('Go Live Date')
                                //                 ->format('Y-m-d')
                                //                 ->displayFormat('d/m/Y')
                                //                 ->default(fn ($record) => $record->softwareHandover()->latest('created_at')->first()?->go_live_date ?? null)
                                //                 // Only require go_live_date when status is NOT InActive
                                //                 ->required(fn (callable $get) => $get('status_handover') !== 'InActive')
                                //                 // Hide field when status is InActive
                                //                 ->visible(fn (callable $get) => $get('status_handover') == 'Closed'),
                                //         ])
                                //         ->action(function (Lead $lead, array $data) {
                                //             // Get the latest software handover record
                                //             $handover = $lead->softwareHandover()->latest('created_at')->first();

                                //             // Prepare update data - don't include go_live_date when status is Inactive
                                //             $updateData = [
                                //                 'status_handover' => $data['status_handover'],
                                //             ];

                                //             // Only include go_live_date if status is not Inactive
                                //             if ($data['status_handover'] !== 'Inactive') {
                                //                 $updateData['go_live_date'] = $data['go_live_date'];
                                //             }

                                //             if ($handover) {
                                //                 // Update existing software handover
                                //                 $handover->update($updateData);

                                //                 Notification::make()
                                //                     ->title('Project Information Updated')
                                //                     ->success()
                                //                     ->send();
                                //             } else {
                                //                 // Create new software handover
                                //                 $lead->softwareHandover()->create(array_merge($updateData, [
                                //                     'status' => 'Completed',
                                //                 ]));

                                //                 Notification::make()
                                //                     ->title('Project Information Created')
                                //                     ->success()
                                //                     ->send();
                                //             }
                                //         }),
                                // ])
                                ->schema([
                                    View::make('components.project-information'),
                                ]),

                            Section::make('Reseller Details')
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ])
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            View::make('components.reseller-details')
                                                ->extraAttributes(fn ($record) => ['record' => $record]),
                                        ]),
                                ])
                                ->headerActions([
                                    Action::make('assign_reseller')
                                        ->label('Edit')
                                        ->icon('heroicon-o-pencil')
                                        ->visible(fn (Lead $lead) =>
                                            // First check if user role is not 4 or 5
                                            in_array(auth()->user()->role_id, [1, 2, 3]) &&

                                            // If user is role 2 (salesperson), they can only edit their own leads
                                            (auth()->user()->role_id != 2 || (auth()->user()->role_id == 2 && $lead->salesperson == auth()->user()->id)) &&

                                            // Then check if lead owner exists or salesperson exists
                                            (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                        )
                                        ->modalHeading('Assign Reseller to Lead')
                                        ->modalSubmitActionLabel('Assign')
                                        ->form([
                                            Select::make('reseller_id')
                                                ->label('Reseller')
                                                ->options(function () {
                                                    return \App\Models\Reseller::pluck('company_name', 'id')
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            // Update the lead with reseller information
                                            $lead->updateQuietly([
                                                'reseller_id' => $data['reseller_id'],
                                            ]);

                                            $resellerName = \App\Models\Reseller::find($data['reseller_id'])->company_name ?? 'Unknown Reseller';

                                            // Log this action
                                            activity()
                                                ->causedBy(auth()->user())
                                                ->performedOn($lead)
                                                ->log('Assigned to reseller: ' . $resellerName);

                                            Notification::make()
                                                ->title('Reseller Assigned')
                                                ->success()
                                                ->body('This lead has been assigned to ' . $resellerName)
                                                ->send();
                                        }),
                                    Action::make('reset_reseller')
                                        ->label('Reset')
                                        ->color('danger')
                                        ->visible(fn (Lead $lead) => !is_null($lead->reseller_id)) // Only show when there's a reseller assigned
                                        ->modalHeading('Remove Assigned Reseller')
                                        ->modalDescription('Are you sure you want to remove the assigned reseller from this lead?')
                                        ->modalSubmitActionLabel('Reset')
                                        ->requiresConfirmation() // Add confirmation step
                                        ->action(function (Lead $lead) {
                                            // Get reseller name for activity log before removing it
                                            $resellerName = 'Unknown Reseller';
                                            if ($lead->reseller_id) {
                                                $reseller = \App\Models\Reseller::find($lead->reseller_id);
                                                if ($reseller) {
                                                    $resellerName = $reseller->company_name;
                                                }
                                            }

                                            // Update the lead to remove reseller information
                                            $lead->updateQuietly([
                                                'reseller_id' => null,
                                            ]);

                                            // Log this action
                                            activity()
                                                ->causedBy(auth()->user())
                                                ->performedOn($lead)
                                                ->log('Removed reseller: ' . $resellerName);

                                            Notification::make()
                                                ->title('Reseller Removed')
                                                ->success()
                                                ->body('The reseller has been removed from this lead')
                                                ->send();
                                        }),
                                ])
                        ])->columnSpan(1),
                ]),
        ];
    }
}
