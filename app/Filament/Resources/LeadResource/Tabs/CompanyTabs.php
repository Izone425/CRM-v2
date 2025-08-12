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
                            ->icon('heroicon-o-briefcase')
                            ->headerActions([
                                Action::make('edit_company_detail')
                                    ->label('Edit') // Button label
                                    ->modalHeading('Edit Information') // Modal heading
                                    ->visible(fn (Lead $lead) =>
                                        !in_array(auth()->user()->role_id, [4, 5]) &&
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
                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']);

                                        // Add the rest of the fields that don't have the restriction
                                        $schema[] = TextInput::make('company_address1')
                                            ->label('Company Address 1')
                                            ->maxLength(40)
                                            ->default($record->companyDetail->company_address1 ?? '-')
                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']);

                                        $schema[] = TextInput::make('company_address2')
                                            ->label('Company Address 2')
                                            ->maxLength(40)
                                            ->default($record->companyDetail->company_address2 ?? '-')
                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']);

                                        $schema[] = Grid::make(3) // Create a 3-column grid
                                            ->schema([
                                                TextInput::make('postcode')
                                                    ->label('Postcode')
                                                    ->default($record->companyDetail->postcode ?? '-'),

                                                Select::make('state')
                                                    ->label('State')
                                                    ->options(function () {
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            // Map 3-letter country codes to full country names
                                                            return collect($countries)->mapWithKeys(function ($country) {
                                                                return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                            })->toArray();
                                                        }

                                                        return [];
                                                    })
                                                    ->dehydrateStateUsing(function ($state) {
                                                        // Convert the selected code to the full country name
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            foreach ($countries as $country) {
                                                                if ($country['Code'] === $state) {
                                                                    return ucfirst(strtolower($country['State']));
                                                                }
                                                            }
                                                        }

                                                        return $state; // Fallback to the original state if mapping fails
                                                    })
                                                    ->default($record->companyDetail->state ?? null)
                                                    ->searchable()
                                                    ->preload(),

                                                Select::make('industry')
                                                    ->label('Industry')
                                                    ->placeholder('Select an industry')
                                                    ->default($record->companyDetail->industry ?? 'None')
                                                    ->options(fn () => collect(['None' => 'None'])->merge(Industry::pluck('name', 'name')))
                                                    ->searchable()
                                                    ->required()
                                            ]);

                                        $schema[] = Grid::make(2)
                                            ->schema([
                                                TextInput::make('reg_no_new')
                                                    ->label('New Registration No.')
                                                    ->default($record->companyDetail->reg_no_new ?? '-'),
                                            ]);

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

                                        $record = $lead->companyDetail;
                                        if ($record) {
                                            // Update the existing CompanyDetail record
                                            $record->update($data);

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
                    ])
                    ->columnSpan(2),
                    Grid::make(1) // Nested grid for left side (single column)
                        ->schema([
                            Section::make('Person In-Charge')
                                ->icon('heroicon-o-user')
                                ->headerActions([
                                    Action::make('edit_person_in_charge')
                                        ->label('Edit') // Button label
                                        ->visible(fn (Lead $lead) =>
                                            !in_array(auth()->user()->role_id, [4, 5]) &&
                                            (!is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                        )
                                        ->modalHeading('Edit on Person In-Charge') // Modal heading
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
                            ])->columnSpan(1),
                    Grid::make(1)
                        ->schema([
                            Section::make('Status')
                                ->icon('heroicon-o-information-circle')
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ])
                                ->headerActions([
                                    Action::make('archive')
                                        ->label(__('Edit'))
                                        ->visible(fn (Lead $lead) =>
                                            !in_array(auth()->user()->role_id, [4, 5]) &&
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
                            Section::make('Project Information')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->extraAttributes([
                                    'style' => 'background-color: #fff5f5; border: dashed; border-color: #feb2b2;'
                                ])
                                ->visible(function (Lead $lead) {
                                    // Admin role always has access
                                    if (auth()->user()->role_id === 3 || auth()->user()->role_id === 5) {
                                        return true;
                                    }

                                    // Check if current user is the implementer for this lead
                                    $latestHandover = $lead->softwareHandover()
                                        ->orderBy('created_at', 'desc')
                                        ->first();

                                    if ($latestHandover && strtolower($latestHandover->implementer) === strtolower(auth()->user()->name)) {
                                        return true;
                                    }

                                    return false;
                                })
                                ->headerActions([
                                    Action::make('edit_project_info')
                                        ->label('Edit')
                                        ->visible(false)
                                        ->modalHeading('Edit Project Information')
                                        ->modalSubmitActionLabel('Save Changes')
                                        ->form([
                                            Select::make('status_handover')
                                                ->label('Project Status')
                                                ->options([
                                                    'InActive' => 'InActive',
                                                    'Closed' => 'Closed',
                                                ])
                                                ->default(fn ($record) => $record->softwareHandover()->latest('created_at')->first()?->status_handover ?? 'Open')
                                                ->reactive()
                                                ->required(),

                                            DatePicker::make('go_live_date')
                                                ->label('Go Live Date')
                                                ->format('Y-m-d')
                                                ->displayFormat('d/m/Y')
                                                ->default(fn ($record) => $record->softwareHandover()->latest('created_at')->first()?->go_live_date ?? null)
                                                // Only require go_live_date when status is NOT InActive
                                                ->required(fn (callable $get) => $get('status_handover') !== 'InActive')
                                                // Hide field when status is InActive
                                                ->visible(fn (callable $get) => $get('status_handover') == 'Closed'),
                                        ])
                                        ->action(function (Lead $lead, array $data) {
                                            // Get the latest software handover record
                                            $handover = $lead->softwareHandover()->latest('created_at')->first();

                                            // Prepare update data - don't include go_live_date when status is Inactive
                                            $updateData = [
                                                'status_handover' => $data['status_handover'],
                                            ];

                                            // Only include go_live_date if status is not Inactive
                                            if ($data['status_handover'] !== 'Inactive') {
                                                $updateData['go_live_date'] = $data['go_live_date'];
                                            }

                                            if ($handover) {
                                                // Update existing software handover
                                                $handover->update($updateData);

                                                Notification::make()
                                                    ->title('Project Information Updated')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                // Create new software handover
                                                $lead->softwareHandover()->create(array_merge($updateData, [
                                                    'status' => 'Completed',
                                                ]));

                                                Notification::make()
                                                    ->title('Project Information Created')
                                                    ->success()
                                                    ->send();
                                            }
                                        }),
                                ])
                                ->schema([
                                    View::make('components.project-information'),
                                ]),
                        ])->columnSpan(1),
                    ]),
                    // Section::make('E-Invoice Details')
                    // ->icon('heroicon-o-document-text')
                    // ->collapsible()
                    // ->collapsed()
                    // ->headerActions([
                    //     Action::make('edit_einvoice_details')
                    //         ->label('Edit')
                    //         ->icon('heroicon-o-pencil')
                    //         ->modalHeading('Edit E-Invoice Details')
                    //         ->modalSubmitActionLabel('Save Changes')
                    //         ->visible(fn (Lead $lead) => !is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                    //         ->form([
                    //             Grid::make(3)
                    //                 ->schema([
                    //                     TextInput::make('pic_email')
                    //                         ->label('1. PIC Email Address')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->pic_email ?? null)
                    //                         ->helperText('(Note: we will contact via this email if we need further information)'),

                    //                     TextInput::make('tin_no')
                    //                         ->label('2. Tax Identification Number (TIN No.)')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->tin_no ?? null)
                    //                         ->helperText('Note: TIN No. must consist of a combination of the TIN Code and set of number'),

                    //                     TextInput::make('new_business_reg_no')
                    //                         ->label('3. New Business Registration Number')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->new_business_reg_no ?? null)
                    //                         ->helperText('(Note: New ROC No. eg 198701006539. If Foreign Country, please input N/A)'),
                    //                 ]),

                    //             Grid::make(3)
                    //                 ->schema([
                    //                     TextInput::make('old_business_reg_no')
                    //                         ->label('4. Old Business Registration Number')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->old_business_reg_no ?? null)
                    //                         ->helperText('(Note: Old ROC No. eg 123456T. If Foreign Country, please input NA)'),

                    //                     TextInput::make('registration_name')
                    //                         ->label('5. Registration Name')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->registration_name ?? null)
                    //                         ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                    //                         ->helperText('(Note: Type only in CAPITAL letter) (as per Business Registration/MyKad/Passport)'),

                    //                     Select::make('identity_type')
                    //                         ->label('6. Identity Type')
                    //                         ->options([
                    //                             'MyKAD' => 'MyKAD',
                    //                             'MyPR' => 'MyPR',
                    //                             'MyKAS' => 'MyKAS',
                    //                             'MyTen' => 'MyTen',
                    //                             'PassP' => 'PassP',
                    //                         ])
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->identity_type ?? null)
                    //                         ->helperText('(Note: For company, please choose MyKAD option)'),
                    //                 ]),

                    //             Grid::make(3)
                    //                 ->schema([
                    //                     Radio::make('tax_classification')
                    //                         ->label('7. Tax Classification')
                    //                         ->options([
                    //                             '0' => 'Individual (0)',
                    //                             '1' => 'Business (1)',
                    //                             '2' => 'Government (2)',
                    //                         ])
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->tax_classification ?? null)
                    //                         ->helperText('(Note: 0 - Individual  1 - Business   2 - Government)'),

                    //                     TextInput::make('sst_reg_no')
                    //                         ->label('8. Sales and Service Tax (SST) Registration Number')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->sst_reg_no ?? null)
                    //                         ->helperText('(Note: No. eg J31-1808-22000109. If don\'t have, please input N/A)'),

                    //                     TextInput::make('msic_code')
                    //                         ->label('9. Business MSIC Code')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->msic_code ?? null)
                    //                         ->helperText('(Note: The value must be in 5 characters) (as per Form C / Annual Return)'),
                    //                 ]),

                    //             Grid::make(3)
                    //                 ->schema([
                    //                     TextInput::make('msic_code_2')
                    //                         ->label('10. Business MSIC Code 2')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->msic_code_2 ?? null)
                    //                         ->helperText('If more than 1 MSIC Code, If don\'t have, please input N/A (5 characters)'),

                    //                     TextInput::make('msic_code_3')
                    //                         ->label('11. Business MSIC Code 3')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->msic_code_3 ?? null)
                    //                         ->helperText('If more than 2 MSIC Code, If don\'t have, please input N/A (5 characters)'),

                    //                     TextInput::make('business_address')
                    //                         ->label('12. Business Address')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->business_address ?? null),
                    //                 ]),

                    //             Grid::make(3)
                    //                 ->schema([
                    //                     TextInput::make('postcode')
                    //                         ->label('13. Postcode')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->postcode ?? null),

                    //                     TextInput::make('contact_number')
                    //                         ->label('14. Contact Number')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->contact_number ?? null)
                    //                         ->helperText('(Finance/Account Department)'),

                    //                     TextInput::make('email_address')
                    //                         ->label('15. Email address')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->email_address ?? null)
                    //                         ->helperText('(Note: this email will be receiving e-invoice from IRBM)'),
                    //                 ]),

                    //             Grid::make(3)
                    //                 ->schema([
                    //                     TextInput::make('city')
                    //                         ->label('16. City')
                    //                         ->required()
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->city ?? null),

                    //                     Select::make('country')
                    //                         ->label('17. Country')
                    //                         ->options([
                    //                             'MYS' => 'Malaysia (MYS)',
                    //                         ])
                    //                         ->default('MYS')
                    //                         ->required(),

                    //                     Select::make('state')
                    //                         ->label('18. State')
                    //                         ->options(function () {
                    //                             $filePath = storage_path('app/public/json/StateCodes.json');

                    //                             if (file_exists($filePath)) {
                    //                                 $countriesContent = file_get_contents($filePath);
                    //                                 $countries = json_decode($countriesContent, true);

                    //                                 return collect($countries)->mapWithKeys(function ($country) {
                    //                                     return [$country['Code'] => ucfirst(strtolower($country['State']))];
                    //                                 })->toArray();
                    //                             }

                    //                             return [];
                    //                         })
                    //                         ->default(fn ($record) => $record->eInvoiceDetail->state ?? null)
                    //                         ->searchable()
                    //                         ->preload(),
                    //                 ]),
                    //         ])
                    //         ->action(function (Lead $lead, array $data) {
                    //             $record = $lead->eInvoiceDetail;
                    //             if ($record) {
                    //                 // Update the existing record
                    //                 $record->update($data);

                    //                 Notification::make()
                    //                     ->title('E-Invoice Details Updated')
                    //                     ->success()
                    //                     ->send();
                    //             } else {
                    //                 // Create a new record
                    //                 $lead->eInvoiceDetail()->create($data);

                    //                 Notification::make()
                    //                     ->title('E-Invoice Details Created')
                    //                     ->success()
                    //                     ->send();
                    //             }
                    //         }),
                    //     ])
                    // ->schema([
                    //     View::make('components.e-invoice-details')
                    //     ->extraAttributes(['poll' => true])
                    // ]),
                Section::make('Reseller Details')
                    ->icon('heroicon-o-building-storefront')
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
                            ->label('Assign Reseller')
                            ->icon('heroicon-o-link')
                            ->visible(function (Lead $lead) {
                                // First check if user has appropriate role
                                if (!in_array(auth()->user()->role_id, [1, 2, 3])) {
                                    return false;
                                }

                                // Then apply the original condition
                                return !is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson));
                            })
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
                            ->icon('heroicon-o-x-mark')
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
        ];
    }
}
