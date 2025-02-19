<?php

namespace App\Filament\Resources;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LeadResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Lead;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Support\Enums\FontWeight;
use App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $label = 'leads';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    public $modules;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1) // Define a single-column grid layout
                    ->schema([
                        Forms\Components\Tabs::make()->tabs([
                            Forms\Components\Tabs\Tab::make('Lead')->schema([
                                Forms\Components\Grid::make(4) // A three-column grid for overall layout
                                ->schema([
                                    // Left-side layout
                                    Forms\Components\Grid::make(2) // Nested grid for left side (single column)
                                        ->schema([
                                            Forms\Components\Section::make('Lead Details')
                                                ->icon('heroicon-o-briefcase')
                                                ->schema([
                                                    Forms\Components\Grid::make(2) // Two columns layout for Lead Details
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('lead_id')
                                                                ->label('Lead ID')
                                                                ->content(fn ($record) => $record ? str_pad($record->id, 5, '0', STR_PAD_LEFT) : '-'),
                                                            Forms\Components\Placeholder::make('lead_source')
                                                                ->label('Lead Source')
                                                                ->content(fn ($record) => $record->leadSource?->platform ?? '-'),
                                                            Forms\Components\Placeholder::make('lead_created_by')
                                                                ->label('Lead Created By')
                                                                ->content(function ($record) {
                                                                    if (!$record) {
                                                                        return '-';
                                                                    }

                                                                    $activityLog = $record->activityLogs()
                                                                        ->where('description', 'New lead created')
                                                                        ->where('subject_id', $record->id)
                                                                        ->first();

                                                                    $causerId = $activityLog?->causer_id;

                                                                    if ($causerId) {
                                                                        $user = \App\Models\User::find($causerId);
                                                                        return $user?->name ?? '-';
                                                                    }

                                                                    return '-';
                                                                }),
                                                            Forms\Components\Placeholder::make('lead_created_on')
                                                                ->label('Lead Created On')
                                                                ->content(fn ($record) => $record->created_at?->format('d M Y, H:i') ?? '-'),
                                                            Forms\Components\Placeholder::make('company_size')
                                                                ->label('Company Size')
                                                                ->content(fn ($record) => $record->getCompanySizeLabelAttribute() ?? '-'),
                                                            Forms\Components\Placeholder::make('headcount')
                                                                ->label('Headcount')
                                                                ->content(fn ($record) => $record->company_size ?? '-'),
                                                        ]),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\Section::make('Progress')
                                                    ->icon('heroicon-o-calendar-days')
                                                    ->schema([
                                                        Forms\Components\Grid::make(1) // Single-column layout for progress
                                                            ->schema([
                                                                Forms\Components\Placeholder::make('days_from_lead_created')
                                                                    ->label('Total Days from Lead Created')
                                                                    ->content(function ($record) {
                                                                        $createdDate = $record->created_at;
                                                                        return $createdDate ? $createdDate->diffInDays(now()) . ' days' : '-';
                                                                    }),

                                                                Forms\Components\Placeholder::make('days_from_new_demo')
                                                                    ->label('Total Days from New Demo')
                                                                    ->content(fn ($record) => $record->calculateDaysFromNewDemo() . ' days'),

                                                                // Empty placeholders to extend section height
                                                                Forms\Components\Placeholder::make('empty1')->label('')->content(''),
                                                                Forms\Components\Placeholder::make('empty2')->label('')->content(''),
                                                                Forms\Components\Placeholder::make('empty3')->label('')->content(''),
                                                            ]),
                                                    ]),
                                            ])
                                            ->columnSpan(1),
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\Section::make('Sales In-Charge')
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ])
                                                    ->icon('heroicon-o-user')
                                                    ->schema([
                                                        Forms\Components\Grid::make(1) // Single column in the right-side section
                                                            ->schema([
                                                                Forms\Components\Placeholder::make('lead Owner')
                                                                    ->label('Lead Owner')
                                                                    ->content(fn ($record) =>  $record->lead_owner ?? 'No Lead Owner'),
                                                                Forms\Components\Placeholder::make('salesperson')
                                                                    ->label('Salesperson')
                                                                    ->content(function ($record) {
                                                                        $salespersonId = $record->salesperson; // Get the salesperson ID
                                                                        $user = \App\Models\User::find($salespersonId); // Find the User by ID
                                                                        return $user->name ?? 'No Salesperson'; // Return the name or fallback to 'No Salesperson'
                                                                    }),
                                                                Actions::make([
                                                                    Actions\Action::make('edit_sales_in_charge')
                                                                        ->label('Edit')
                                                                        ->visible(function ($record) {
                                                                            return (auth()->user()?->role_id === 1 && !is_null($record->lead_owner)
                                                                            || auth()->user()?->role_id === 3);
                                                                        })
                                                                        ->form(array_merge(
                                                                            auth()->user()->role_id !== 1
                                                                                ? [
                                                                                    Grid::make()
                                                                                        ->schema([
                                                                                            Forms\Components\Select::make('position')
                                                                                                ->label('Lead Owner Role')
                                                                                                ->options([
                                                                                                    'sale_admin' => 'Sales Admin',
                                                                                                ]),
                                                                                            Forms\Components\Select::make('lead_owner')
                                                                                                ->label('Lead Owner')
                                                                                                ->options(
                                                                                                    \App\Models\User::where('role_id', 1)
                                                                                                        ->pluck('name', 'id')
                                                                                                )
                                                                                                ->searchable(),
                                                                                        ])->columns(2),
                                                                                ]
                                                                                : [],
                                                                            [
                                                                                Forms\Components\Select::make('salesperson')
                                                                                    ->label('Salesperson')
                                                                                    ->options(
                                                                                        \App\Models\User::where('role_id', 2)
                                                                                            ->pluck('name', 'id')
                                                                                    )
                                                                                    ->required()
                                                                                    ->searchable(),
                                                                            ]
                                                                        ))
                                                                        ->action(function ($record, $data) {
                                                                            if (!empty($data['salesperson'])) {
                                                                                $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';
                                                                                $record->update(['salesperson' => $data['salesperson']]);
                                                                            }

                                                                            // Check and update lead_owner if it's not null
                                                                            if (!empty($data['lead_owner'])) {
                                                                                $leadOwnerName = \App\Models\User::find($data['lead_owner'])?->name ?? 'Unknown Lead Owner';
                                                                                $record->update(['lead_owner' => $leadOwnerName]);
                                                                            }

                                                                            $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                                                                                ->orderByDesc('created_at')
                                                                                ->take(2)
                                                                                ->get();

                                                                            // Check if at least two logs exist
                                                                            if (auth()->user()->role_id == 3) {
                                                                                $causer_id = auth()->user()->id;
                                                                                $causer_name = \App\Models\User::find($causer_id)->name;
                                                                                $latestActivityLogs[0]->update([
                                                                                    'description' => 'Lead Owner updated by '. $causer_name . ": " . $leadOwnerName,
                                                                                ]);

                                                                                // Update the second activity log
                                                                                $latestActivityLogs[1]->update([
                                                                                    'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                                                                                ]);
                                                                            }else{
                                                                                $causer_id = auth()->user()->id;
                                                                                $causer_name = \App\Models\User::find($causer_id)->name;
                                                                                // $latestActivityLogs[0]->delete();
                                                                                $latestActivityLogs[0]->update([
                                                                                    'description' => 'Salesperson updated by '. $causer_name . ": " . $salespersonName,
                                                                                ]);
                                                                            }
                                                                            // Log the activity for auditing
                                                                            activity()
                                                                                ->causedBy(auth()->user())
                                                                                ->performedOn($record);

                                                                            Notification::make()
                                                                            ->title('Sales In-Charge Edited Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                        })
                                                                        ->modalHeading('Edit Sales In-Charge')
                                                                        ->modalDescription('Changing the Lead Owner and Salesperson will allow the new staff
                                                                                            to take action on the current and future follow-ups only.')
                                                                        ->modalSubmitActionLabel('Save Changes'),
                                                                ]),
                                                            ]),
                                                    ])
                                                    ->columnSpan(1), // Right side spans 1 column
                                            ])->columnSpan(1),
                                ]),
                            ]),
                            Forms\Components\Tabs\Tab::make('Company')->schema([
                                Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Section::make('Company Details')
                                                ->icon('heroicon-o-briefcase')
                                                ->headerActions([
                                                    Action::make('edit_company_detail')
                                                        ->label('Edit') // Button label
                                                        ->modalHeading('Edit Information') // Modal heading
                                                        ->visible(fn (Lead $lead) => !is_null($lead->lead_owner))
                                                        ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                        ->form([ // Define the form fields to show in the modal
                                                            Forms\Components\TextInput::make('company_name')
                                                                ->label('Company Name')
                                                                ->default(fn ($record) => strtoupper($record->companyDetail->company_name ?? '-'))
                                                                ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),
                                                            Forms\Components\TextInput::make('company_address1')
                                                                ->label('Company Address 1')
                                                                ->default(fn ($record) => $record->companyDetail->company_address1 ?? '-')
                                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                                                            Forms\Components\TextInput::make('company_address2')
                                                                ->label('Company Address 2')
                                                                ->default(fn ($record) => $record->companyDetail->company_address2 ?? '-')
                                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                                                            Forms\Components\Grid::make(3) // Create a 3-column grid
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('postcode')
                                                                        ->label('Postcode')
                                                                        ->default(fn ($record) => $record->companyDetail->postcode ?? '-'),

                                                                    Forms\Components\Select::make('state')
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
                                                                        ->default(fn ($record) => $record->companyDetail->state ?? null)
                                                                        ->searchable()
                                                                        ->preload(),

                                                                    Forms\Components\Select::make('industry')
                                                                        ->label('Industry')
                                                                        ->options([
                                                                            'Manufacturing' => 'Manufacturing',
                                                                            'Information Technology' => 'Information Technology',
                                                                            'Finance' => 'Finance',
                                                                            'Healthcare' => 'Healthcare',
                                                                            'Education' => 'Education',
                                                                            'Retail' => 'Retail',
                                                                            'Other' => 'Other',
                                                                        ])
                                                                        ->default(fn ($record) => $record->companyDetail->industry ?? null)
                                                                        ->searchable()
                                                                        ->preload(),
                                                                ]),
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
                                                    Forms\Components\Grid::make(2) // Two columns layout for Lead Details
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('company_name')
                                                                ->label('Company Name')
                                                               ->content(fn ($record) => $record->companyDetail->company_name ?? '-'),
                                                            Forms\Components\Placeholder::make('postcode')
                                                               ->label('Postcode')
                                                               ->content(fn ($record) => $record->companyDetail->postcode ?? '-'),
                                                            Forms\Components\Placeholder::make('company_address1')
                                                                ->label('Company Address 1')
                                                                ->content(fn ($record) => $record->companyDetail->company_address1 ?? '-'),
                                                            Forms\Components\Placeholder::make('state')
                                                                ->label('State')
                                                                ->content(fn ($record) => $record->companyDetail->state ?? '-'),
                                                            Forms\Components\Placeholder::make('company_address2')
                                                                ->label('Company Address 2')
                                                                ->content(fn ($record) => $record->companyDetail->company_address2 ?? '-'),
                                                            Forms\Components\Placeholder::make('industry')
                                                                ->label('Industry')
                                                                ->content(fn ($record) => $record->companyDetail->industry ?? '-'),
                                                        ]),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                        Forms\Components\Grid::make(1) // Nested grid for left side (single column)
                                            ->schema([
                                                Forms\Components\Section::make('Person In-Charge')
                                                    ->icon('heroicon-o-user')
                                                    ->headerActions([
                                                        Action::make('edit_person_in_charge')
                                                            ->label('Edit') // Button label
                                                            ->visible(fn (Lead $lead) => !is_null($lead->lead_owner))
                                                            ->modalHeading('Edit on Person In-Charge') // Modal heading
                                                            ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                            ->form([ // Define the form fields to show in the modal
                                                                Forms\Components\TextInput::make('name')
                                                                    ->label('Name')
                                                                    ->required()
                                                                    ->default(fn ($record) => $record->companyDetail->name ?? null)
                                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('name', strtoupper($state))),
                                                                Forms\Components\TextInput::make('email')
                                                                    ->label('Email')
                                                                    ->required()
                                                                    ->default(fn ($record) => $record->companyDetail->email ?? null),
                                                                Forms\Components\TextInput::make('contact_no')
                                                                    ->label('Contact No.')
                                                                    ->required()
                                                                    ->default(fn ($record) => $record->companyDetail->contact_no ?? null),
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

                                                        Forms\Components\Placeholder::make('name')
                                                            ->label('Name')
                                                            ->content(fn ($record) => $record->companyDetail->name ?? '-'),
                                                        Forms\Components\Placeholder::make('contact_no')
                                                            ->label('Contact No.')
                                                            ->content(fn ($record) => $record->companyDetail->contact_no ?? '-'),
                                                        Forms\Components\Placeholder::make('email')
                                                            ->label('Email Address')
                                                            ->content(fn ($record) => $record->companyDetail->email ?? '-'),
                                                    ]),
                                                ])->columnSpan(1),
                                        Forms\Components\Grid::make(1) // Nested grid for left side (single column)
                                            ->schema([
                                                Forms\Components\Section::make('Status')
                                                    ->icon('heroicon-o-information-circle')
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Grid::make(1) // Single column in the right-side section
                                                            ->schema([
                                                                Forms\Components\Placeholder::make('deal_amount')
                                                                    ->label('Deal Amount')
                                                                    ->content(function (Lead $record) {
                                                                        $latestQuotation = $record->quotations()->latest('created_at')->first();

                                                                        $currency = $latestQuotation->currency ?? 'USD';

                                                                        $dealAmount = $record->deal_amount ? number_format($record->deal_amount, 2) : '0.00';

                                                                        return $currency === 'MYR' ? "RM {$dealAmount}" : "$ {$dealAmount}";
                                                                    }),
                                                                Forms\Components\Placeholder::make('status')
                                                                    ->label('Status')
                                                                    ->content(fn ($record) => ($record->stage ?? $record->categories)
                                                                    ? ($record->stage ?? $record->categories) . ' : ' . $record->lead_status
                                                                    : '-'),
                                                                // Actions::make([
                                                                //     Actions\Action::make('edit_status')
                                                                //         ->label('Edit')
                                                                //         ->visible(fn (Lead $lead) => !is_null($lead->lead_owner))
                                                                //         ->action(function ($record, $data) {
                                                                //             // Extract selected values
                                                                //             $newStage = $data['new_stage'];
                                                                //             $newLeadStatus = $data['new_lead_status'];

                                                                //             // Update the record based on the selected values
                                                                //             $record->update([
                                                                //                 'stage' => $newStage === 'Inactive' ? null : $newStage, // Set stage to null if $newStage is 'Inactive'
                                                                //                 'lead_status' => $newLeadStatus,
                                                                //                 'categories' => $newStage === 'Inactive' ? $newStage : $record->categories, // Update categories to newStage if it is 'Inactive'
                                                                //             ]);

                                                                //             // Fetch latest activity logs
                                                                //             $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                                                                //                 ->orderByDesc('created_at')
                                                                //                 ->take(2)
                                                                //                 ->get();

                                                                //             if ($latestActivityLogs->count() >= 2) {
                                                                //                 // Update the first activity log
                                                                //                 $latestActivityLogs[0]->update([
                                                                //                     'description' => 'Lead Stage updated to: ' . $newStage,
                                                                //                 ]);

                                                                //                 // Update the second activity log
                                                                //                 $latestActivityLogs[1]->update([
                                                                //                     'description' => 'Lead Status updated to: ' . $newLeadStatus,
                                                                //                 ]);
                                                                //             } elseif ($latestActivityLogs->count() === 1) {
                                                                //                 // Update the single existing log if only one exists
                                                                //                 $latestActivityLogs[0]->update([
                                                                //                     'description' => 'Lead Stage updated to: ' . $newStage . ' and Lead Status updated to: ' . $newLeadStatus,
                                                                //                 ]);
                                                                //             }

                                                                //             activity()
                                                                //                 ->causedBy(auth()->user())
                                                                //                 ->performedOn($record);

                                                                //             // Notify the user of successful update
                                                                //             Notification::make()
                                                                //                 ->title('Sales In-Charge Edited Successfully')
                                                                //                 ->success()
                                                                //                 ->send();
                                                                //         })
                                                                //         ->form([
                                                                //             Grid::make() // Three columns layout
                                                                //             ->schema([
                                                                //                 Forms\Components\Placeholder::make('current_status')
                                                                //                     ->label('Current Status')
                                                                //                     ->content(fn ($record) => ($record->stage ?? $record->categories)
                                                                //                     ? ($record->stage ?? $record->categories) . ' : ' . $record->lead_status
                                                                //                     : '-'),
                                                                //                 Forms\Components\Placeholder::make('arrow') // Arrow in the middle column
                                                                //                     ->content('----------->') // Unicode arrow or any arrow symbol
                                                                //                     ->columnSpan(1)
                                                                //                     ->label(''),
                                                                //                 Forms\Components\Select::make('new_stage')
                                                                //                     ->label('New Stage')
                                                                //                     ->options([
                                                                //                         'New' => 'New',
                                                                //                         'Transfer' => 'Transfer',
                                                                //                         'Demo' => 'Demo',
                                                                //                         'Follow Up' => 'Follow Up',
                                                                //                         'Inactive' => 'Inactive',
                                                                //                     ])
                                                                //                     ->required()
                                                                //                     ->reactive(), // Make this field reactive

                                                                //                 Forms\Components\Select::make('new_lead_status')
                                                                //                     ->label('New Lead Status')
                                                                //                     ->options(fn ($get) => match ($get('new_stage')) {
                                                                //                         'New' => [
                                                                //                             'None' => 'None',
                                                                //                         ],
                                                                //                         'Transfer' => [
                                                                //                             'New' => 'New',
                                                                //                             'Under Review' => 'Under Review',
                                                                //                             'RFQ-Transfer' => 'RFQ-Transfer',
                                                                //                             'Pending Demo' => 'Pending Demo',
                                                                //                             'Demo Cancelled' => 'Demo Cancelled',
                                                                //                         ],
                                                                //                         'Demo' => [
                                                                //                             'Demo-Assigned' => 'Demo-Assigned',
                                                                //                         ],
                                                                //                         'Follow Up' => [
                                                                //                             'RFQ-Follow Up' => 'RFQ-Follow Up',
                                                                //                             'Hot' => 'Hot',
                                                                //                             'Warm' => 'Warm',
                                                                //                             'Cold' => 'Cold',
                                                                //                         ],
                                                                //                         'Inactive' => [
                                                                //                             'Junk' => 'Junk',
                                                                //                             'On Hold' => 'On Hold',
                                                                //                             'Lost' => 'Lost',
                                                                //                             'No Response' => 'No Response',
                                                                //                             'Closed' => 'Closed',
                                                                //                         ],
                                                                //                         default => [
                                                                //                             'None' => 'None',
                                                                //                             'New' => 'New',
                                                                //                             'RFQ-Transfer' => 'RFQ-Transfer',
                                                                //                             'Pending Demo' => 'Pending Demo',
                                                                //                             'Under Review' => 'Under Review',
                                                                //                             'Demo Cancelled' => 'Demo Cancelled',
                                                                //                             'Demo-Assigned' => 'Demo-Assigned',
                                                                //                             'RFQ-Follow Up' => 'RFQ-Follow Up',
                                                                //                             'Hot' => 'Hot',
                                                                //                             'Warm' => 'Warm',
                                                                //                             'Cold' => 'Cold',
                                                                //                             'Junk' => 'Junk',
                                                                //                             'On Hold' => 'On Hold',
                                                                //                             'Lost' => 'Lost',
                                                                //                             'No Response' => 'No Response',
                                                                //                             'Closed' => 'Closed',
                                                                //                         ],
                                                                //                     })
                                                                //                     ->required(),
                                                                //             ])->columns(4),

                                                                //         ])
                                                                //         ->modalHeading('Edit Status')
                                                                //         ->modalDescription('You are about to change the status of this lead. Changing the lead
                                                                //                             status may trigger automated actions based on the new status.')
                                                                //         ->modalSubmitActionLabel('Confirm')
                                                                //         ->extraAttributes(function () {
                                                                //             // Hide the action by applying a CSS class when the user's role_id is 1 or 2
                                                                //             return auth()->user()->role_id === 2
                                                                //                 ? ['class' => 'hidden']
                                                                //                 : [];
                                                                //         }),
                                                                //     ]),
                                                            ]),
                                                    ])
                                            ])->columnSpan(1),
                                        ]),
                            ]),
                            Tab::make('System')
                                ->schema([
                                    Forms\Components\Tabs::make('Phases')
                                        ->tabs([
                                            Forms\Components\Tabs\Tab::make('Phase 1')
                                                ->schema([
                                                    Forms\Components\Section::make('Phase 1')
                                                        ->description(fn ($record) =>
                                                            $record && $record->systemQuestion
                                                                ? 'Updated by ' . ($record->systemQuestion->causer_name ?? 'Unknown') . ' on ' .
                                                                ($record->systemQuestion->updated_at?->format('F j, Y, g:i A') ?? 'N/A')
                                                                : null
                                                        )
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('modules')
                                                                ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->modules ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('existing_system')
                                                                ->label('2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->existing_system ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('usage_duration')
                                                                ->label('3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->usage_duration ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('expired_date')
                                                                ->label('4. WHEN IS THE EXPIRED DATE?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->expired_date
                                                                    ? \Carbon\Carbon::createFromFormat('Y-m-d', $record->systemQuestion->expired_date)->format('d/m/Y')
                                                                    : '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('reason_for_change')
                                                                ->label('5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->reason_for_change ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('staff_count')
                                                                ->label('6. HOW MANY STAFF DO YOU HAVE?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->staff_count ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('hrdf_contribution')
                                                                ->label('7. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->hrdf_contribution ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Forms\Components\Actions\Action::make('update')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->where('status', 'Done') // Ensure only 'Done' demos are considered
                                                                        ->latest() // Get the latest 'Done' demo
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false; // If no 'Done' demo is found, hide the field
                                                                    }

                                                                    // Check if the latest 'Done' demo was updated within the last 48 hours
                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Forms\Components\TextInput::make('modules')
                                                                        ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->modules),
                                                                    Forms\Components\TextInput::make('existing_system')
                                                                        ->label('2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->existing_system),
                                                                    Forms\Components\TextInput::make('usage_duration')
                                                                        ->label('3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->usage_duration),
                                                                    Forms\Components\DatePicker::make('expired_date')
                                                                        ->label('4. WHEN IS THE EXPIRED DATE?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->expired_date),
                                                                    Forms\Components\Textarea::make('reason_for_change')
                                                                        ->label('5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->reason_for_change)
                                                                        ->rows(3),
                                                                    Forms\Components\TextInput::make('staff_count')
                                                                        ->label('6. HOW MANY STAFF DO YOU HAVE?')
                                                                        ->numeric()
                                                                        ->default(fn ($record) => $record?->systemQuestion?->staff_count),
                                                                    Forms\Components\Select::make('hrdf_contribution')
                                                                        ->label('7. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                                        ->options([
                                                                            'Yes' => 'Yes',
                                                                            'No' => 'No',
                                                                        ])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->hrdf_contribution),
                                                                ])
                                                                ->action(function (Lead $lead, array $data) {
                                                                    // Retrieve the current lead's systemQuestion
                                                                    $record = $lead->systemQuestion;

                                                                    if ($record) {
                                                                        // Include causer_id in the data
                                                                        $data['causer_name'] = auth()->user()->name;
                                                                        $record->updated_at_phase_1 = now();

                                                                        // Update the existing SystemQuestion record
                                                                        $record->update($data);

                                                                        Notification::make()
                                                                            ->title('Updated Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    } else {
                                                                        // Add causer_id to the data for the new record
                                                                        $data['causer_name'] = auth()->user()->name;

                                                                        // Create a new SystemQuestion record via the relation
                                                                        $lead->systemQuestion()->create($data);

                                                                        Notification::make()
                                                                            ->title('Created Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    }
                                                                }),
                                                        ])
                                                ]),
                                            Forms\Components\Tabs\Tab::make('Phase 2')
                                                ->schema([
                                                    Forms\Components\Section::make('Phase 2')
                                                        ->description(fn ($record) =>
                                                            $record && $record->systemQuestion
                                                                ? 'Updated by ' . ($record->systemQuestion->causer_name ?? 'Unknown') . ' on ' .
                                                                ($record->systemQuestion->updated_at?->format('F j, Y, g:i A') ?? 'N/A')
                                                                : null
                                                        )
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('modules')
                                                                ->label('1.  PROSPECT QUESTION – NEED TO REFER SUPPORT TEAM.')
                                                                ->content(fn ($record) => $record?->systemQuestion?->modules ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('existing_system')
                                                                ->label('2. PROSPECT CUSTOMIZATION – NEED TO REFER PRODUCT TEAM.')
                                                                ->content(fn ($record) => $record?->systemQuestion?->existing_system ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Forms\Components\Actions\Action::make('update_phase2')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->where('status', 'Done') // Ensure only 'Done' demos are considered
                                                                        ->latest() // Get the latest 'Done' demo
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false; // If no 'Done' demo is found, hide the field
                                                                    }

                                                                    // Check if the latest 'Done' demo was updated within the last 48 hours
                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Forms\Components\TextInput::make('modules')
                                                                        ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->modules),
                                                                ])
                                                                ->action(function (Lead $lead, array $data) {
                                                                    // Retrieve the current lead's systemQuestion
                                                                    $record = $lead->systemQuestion;

                                                                    if ($record) {
                                                                        // // Include causer_id in the data
                                                                        // $data['causer_name'] = auth()->user()->name;
                                                                        // $record->causer_name_phase3 = auth()->user()->name;
                                                                        // $record->updated_at_phase_3 = now();
                                                                        // // Update the existing SystemQuestion record
                                                                        // $record->update($data);

                                                                        Notification::make()
                                                                            ->title('Updated Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    } else {
                                                                        // // Add causer_id to the data for the new record
                                                                        // $data['causer_name'] = auth()->user()->name;

                                                                        // // Create a new SystemQuestion record via the relation
                                                                        // $lead->systemQuestion()->create($data);

                                                                        Notification::make()
                                                                            ->title('Created Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    }
                                                                }),
                                                        ])
                                                ]),
                                            Forms\Components\Tabs\Tab::make('Phase 3')
                                                ->schema([
                                                    Forms\Components\Section::make('Phase 3')
                                                        ->description(function ($record) {
                                                            if ($record && $record->systemQuestionPhase3 && !empty($record->systemQuestionPhase3->updated_at)) {
                                                                return 'Updated by ' . ($record->systemQuestionPhase3->causer_name ?? 'Unknown') . ' on ' .
                                                                    \Carbon\Carbon::parse($record->systemQuestionPhase3->updated_at)->format('F j, Y, g:i A');
                                                            }

                                                            return null; // Return null if no update exists
                                                        })
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('percentage')
                                                                ->label('1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?')
                                                                ->content(fn ($record) => $record?->systemQuestionPhase3?->percentage ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('vendor')
                                                                ->label('2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?')
                                                                ->content(fn ($record) => $record?->systemQuestionPhase3?->vendor ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('plan')
                                                                ->label('3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?')
                                                                ->content(fn ($record) => $record?->systemQuestionPhase3?->plan ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Forms\Components\Placeholder::make('finalise')
                                                                ->label('4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?')
                                                                ->content(fn ($record) => $record?->systemQuestionPhase3?->finalise ?? '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Forms\Components\Actions\Action::make('update_phase3')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->where('status', 'Done') // Ensure only 'Done' demos are considered
                                                                        ->latest() // Get the latest 'Done' demo
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false; // If no 'Done' demo is found, hide the field
                                                                    }

                                                                    // Check if the latest 'Done' demo was updated within the last 48 hours
                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Forms\Components\TextInput::make('percentage')
                                                                        ->label('1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?')
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->percentage),
                                                                    Forms\Components\TextInput::make('vendor')
                                                                        ->label('2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?')
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->vendor),
                                                                    Forms\Components\TextInput::make('plan')
                                                                        ->label('3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?')
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->plan),
                                                                    Forms\Components\TextInput::make('finalise')
                                                                        ->label('4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?')
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->finalise),
                                                                ])
                                                                ->action(function (Lead $lead, array $data) {
                                                                    // Retrieve the current lead's systemQuestion
                                                                    $record = $lead->systemQuestionPhase3;

                                                                    if ($record) {
                                                                        // Include causer_id in the data
                                                                        $record->systemQuestionPhase3->causer_name = auth()->user()->name;
                                                                        $record->systemQuestionPhase3->updated_at = now();

                                                                        // Update the existing SystemQuestion record
                                                                        $record->update($data);

                                                                        Notification::make()
                                                                            ->title('Updated Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    } else {
                                                                        // Add causer_id to the data for the new record
                                                                        $data['causer_name'] = auth()->user()->name;

                                                                        // Create a new SystemQuestion record via the relation
                                                                        $lead->systemQuestionPhase3()->create($data);

                                                                        Notification::make()
                                                                            ->title('Created Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    }
                                                                }),
                                                        ])
                                                ]),
                                    ])
                                ]),
                            Forms\Components\Tabs\Tab::make('Refer & Earn')
                            ->schema([
                                Forms\Components\Grid::make(1) // Main grid for all sections
                                    ->schema([
                                        // First row: Referral Details (From and Refer To)
                                        Forms\Components\Grid::make(2) // Three columns layout: From, Arrow, Refer To
                                            ->schema([
                                                // From Section
                                                Forms\Components\Section::make('From')
                                                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                                                    ->schema([
                                                        Forms\Components\Grid::make(2) // Create a grid with two columns
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('from_company')
                                                                ->label('COMPANY')
                                                                ->content(fn ($record) => $record?->referralDetail?->company ?? '-'),
                                                            Forms\Components\Placeholder::make('from_remark')
                                                                ->label('REMARK')
                                                                ->content(fn ($record) => $record?->referralDetail?->remark ?? '-'),
                                                        ]),
                                                        Forms\Components\Placeholder::make('from_name')
                                                            ->label('NAME')
                                                            ->content(fn ($record) => $record?->referralDetail?->name ?? '-'),
                                                        Forms\Components\Placeholder::make('from_email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record?->referralDetail?->email ?? '-'),
                                                        Forms\Components\Placeholder::make('from_contact')
                                                            ->label('CONTACT NO.')
                                                            ->content(fn ($record) => $record?->referralDetail?->contact_no ?? '-'),
                                                    ])
                                                    ->columnSpan(1)
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ]),
                                                // // Arrow in the center
                                                // Forms\Components\Placeholder::make('arrow')
                                                //     ->content('→') // Arrow symbol
                                                //     ->columnSpan(1)
                                                //     ->label(''),

                                                // Refer To Section
                                                Forms\Components\Section::make('Refer to')
                                                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('to_company')
                                                            ->label('COMPANY')
                                                            ->content(fn ($record) => $record->companyDetail->company_name ?? null),
                                                        Forms\Components\Placeholder::make('to_name')
                                                            ->label('NAME')
                                                            ->content(fn ($record) => $record->name ?? null),
                                                        Forms\Components\Placeholder::make('to_email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record->email ?? null),
                                                        Forms\Components\Placeholder::make('to_contact')
                                                            ->label('CONTACT NO.')
                                                            ->content(fn ($record) => $record->phone ?? null),
                                                    ])
                                                    ->columnSpan(1)
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ]),
                                            ])
                                            ->columns(2),

                                        // Second row: Bank Details
                                        Forms\Components\Section::make('Bank Details')
                                            ->icon('heroicon-o-chat-bubble-left')
                                            ->extraAttributes([
                                                'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                            ])
                                            ->headerActions([
                                                Action::make('Edit')
                                                    ->label('Edit') // Button label
                                                    ->modalHeading('Edit Information') // Modal heading
                                                    ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                    ->form([ // Define the form fields to show in the modal
                                                        Forms\Components\TextInput::make('full_name')
                                                            ->label('FULL NAME')
                                                            ->default(fn ($record) => $record?->bankDetail?->full_name ?? null),
                                                        Forms\Components\TextInput::make('ic')
                                                            ->label('IC NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->ic ?? null),
                                                        Forms\Components\TextInput::make('tin')
                                                            ->label('TIN NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->tin ?? null),
                                                        Forms\Components\TextInput::make('bank_name')
                                                            ->label('BANK NAME')
                                                            ->default(fn ($record) => $record?->bankDetail?->bank_name ?? null),
                                                        Forms\Components\TextInput::make('bank_account_no')
                                                            ->label('BANK ACCOUNT NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->bank_account_no ?? null),
                                                        Forms\Components\TextInput::make('contact_no')
                                                            ->label('CONTACT NUMBER')
                                                            ->default(fn ($record) => $record?->bankDetail?->contact_no ?? null),
                                                        Forms\Components\TextInput::make('email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->default(fn ($record) => $record?->bankDetail?->email ?? null),
                                                        Forms\Components\Select::make('referral_payment_status')
                                                            ->label('REFERRAL PAYMENT STATUS')
                                                            ->default(fn ($record) => $record?->bankDetail?->payment_referral_status ?? null)
                                                            ->options([
                                                                'PENDING' => 'Pending',
                                                                'PAID' => 'Paid',
                                                                'PROCESSING' => 'Processing',
                                                            ]),
                                                        Forms\Components\TextInput::make('remark')
                                                            ->label('REMARK')
                                                            ->default(fn ($record) => $record?->bankDetail?->remark ?? null),
                                                    ])
                                                    ->action(function (Lead $lead, array $data) {
                                                        $record = $lead->bankDetail;
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
                                                Forms\Components\Grid::make(4) // Four columns layout
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('full_name')
                                                            ->label('FULL NAME')
                                                            ->content(fn ($record) => $record?->bankDetail?->full_name ?? '-'),
                                                        Forms\Components\Placeholder::make('bank_name')
                                                            ->label('BANK NAME')
                                                            ->content(fn ($record) => $record?->bankDetail?->bank_name ?? '-'),
                                                        Forms\Components\Placeholder::make('contact_no')
                                                            ->label('CONTACT NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->contact_no ?? '-'),
                                                        Forms\Components\Placeholder::make('referral_payment_status')
                                                            ->label('REFERRAL PAYMENT STATUS')
                                                            ->content(fn ($record) => $record?->bankDetail?->referral_payment_status ?? '-'),
                                                        Forms\Components\Placeholder::make('ic_no')
                                                            ->label('IC NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->ic ?? '-'),
                                                        Forms\Components\Placeholder::make('bank_account_no')
                                                            ->label('BANK ACCOUNT NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->bank_account_no ?? '-'),
                                                        Forms\Components\Placeholder::make('email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record?->bankDetail?->email ?? '-'),
                                                        Forms\Components\Placeholder::make('remark')
                                                            ->label('REMARK')
                                                            ->content(fn ($record) => $record?->bankDetail?->remark ?? '-'),
                                                        Forms\Components\Placeholder::make('tin')
                                                            ->label('TIN NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->tin ?? '-'),
                                                    ]),
                                            ]),
                                    ]),
                                ]),
                            Forms\Components\Tabs\Tab::make('Appointment')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager::class,
                                ),
                            ]),
                            Forms\Components\Tabs\Tab::make('Prospect Follow Up')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager::class,
                                ),
                            ]),
                            Forms\Components\Tabs\Tab::make('Quotation')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager::class,
                                ),
                            ]),
                            Forms\Components\Tabs\Tab::make('Proforma Invoice')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager::class,
                                ),
                            ]),
                            Forms\Components\Tabs\Tab::make('Invoice')->schema([

                            ]),
                            Forms\Components\Tabs\Tab::make('Debtor Follow Up')->schema([

                            ]),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultPaginationPageOption(50)
            ->modifyQueryUsing(function ($query) {
                $query->orderByRaw("FIELD(categories, 'New', 'Active', 'Inactive')")
                        ->orderBy('created_at', 'desc');
                return $query;
            })
            ->filters([
                // Filter for Lead Owner
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['demo', 'follow_up'])),

                // Filter for Salesperson
                SelectFilter::make('salesperson')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 2)->pluck('name', 'id')->toArray())
                    ->placeholder('Select Salesperson'),

                //Filter for Created At
                Filter::make('created_at')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('')
                        ->placeholder('Select date range'),
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range from the "start - end" format
                        [$start, $end] = explode(' - ', $data['date_range']);

                        // Ensure valid dates
                        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                        // Apply the filter
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->indicateUsing(function (array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range for display
                        [$start, $end] = explode(' - ', $data['date_range']);

                        return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                            ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                    }
                    return null;
                }),
                // Filter for Categories
                SelectFilter::make('categories')
                    ->label('')
                    ->multiple()
                    ->options(
                        collect(LeadCategoriesEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => ucfirst(strtolower($case->name))])
                            ->toArray()
                    )
                    ->placeholder('Select Category')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['transfer', 'active', 'demo', 'follow_up', 'inactive'])),

                // Filter for Stage
                SelectFilter::make('stage')
                    ->label('')
                    ->multiple()
                    ->options(
                        collect(LeadStageEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name])->toArray()
                    )
                    ->placeholder('Select Stage')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'follow_up', 'inactive'])),

                // Filter for Lead Status
                SelectFilter::make('lead_status')
                    ->label('')
                    ->multiple()
                    ->options(
                        collect(LeadStatusEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->name])->toArray()
                    )
                    ->placeholder('Select Lead Status')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'demo'])),

                Filter::make('appointment_date')
                    ->label('')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('')
                            ->format('Y-m-d') // Ensures compatibility with the database format
                            ->placeholder('Select a date'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['date'])) {
                            $query->whereHas('demoAppointment', function ($subQuery) use ($data) {
                                $subQuery->whereDate('date', $data['date']);
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['date'])
                            ? 'Date: ' . Carbon::parse($data['date'])->format('j M Y')
                            : null;
                    })
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'inactive', 'follow_up', 'transfer'])),

                Filter::make('company_name')
                    ->form([
                        Forms\Components\TextInput::make('company_name')
                            ->hiddenLabel()
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['company_name'])) {
                            $query->whereHas('companyDetail', function ($query) use ($data) {
                                $query->where('company_name', 'like', '%' . $data['company_name'] . '%');
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['company_name'])
                            ? 'Company Name: ' . $data['company_name']
                            : null;
                    }),

                SelectFilter::make('company_size_label') // Use the correct filter key
                    ->label('')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                        'Enterprise' => 'Enterprise',
                    ])
                    ->multiple() // Enables multi-selection
                    ->placeholder('Select Company Size')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['values'])) { // 'values' stores multiple selections
                            $sizeMap = [
                                'Small' => '1-24',
                                'Medium' => '25-99',
                                'Large' => '100-500',
                                'Enterprise' => '501 and Above',
                            ];

                            // Convert selected sizes to DB values
                            $dbValues = collect($data['values'])->map(fn ($size) => $sizeMap[$size] ?? null)->filter();

                            if ($dbValues->isNotEmpty()) {
                                $query->whereHas('companyDetail', function ($query) use ($dbValues) {
                                    $query->whereIn('company_size', $dbValues);
                                });
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return !empty($data['values'])
                            ? 'Company Size: ' . implode(', ', $data['values'])
                            : null;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
                ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('lead_owner')
                    ->label('LEAD OWNER')
                    ->getStateUsing(fn (Lead $record) => $record->lead_owner ?? '-')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['demo', 'follow_up'])),
                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(fn (Lead $record) => \App\Models\User::find($record->salesperson)?->name ?? '-'),
                TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->dateTime('d M Y, h:i A')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('categories')
                    ->label('MAIN CATEGORY')
                    ->alignCenter()
                    // ->visible(function () {
                    //     // dd(request()->query('activeTab')); // Debug the value of activeTab
                    //     return request()->query('activeTab') === 'all';
                    // })
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadCategoriesEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadCategoriesEnum::tryFrom($state)->getColor() . "; border-radius: 25px; width: 60%; height: 27px;"
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['transfer', 'active', 'demo', 'follow_up', 'inactive'])),
                TextColumn::make('stage')
                    ->label('STAGE')
                    ->alignCenter()
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadStageEnum::tryFrom($state)->getColor() . "; border-radius: 25px; width: 90%; height: 27px;"
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'follow_up', 'inactive'])),
                TextColumn::make('lead_status')
                    ->label('LEAD STATUS')
                    ->alignCenter()
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadStatusEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadStatusEnum::tryFrom($state)->getColor() . ";" .
                              "border-radius: 25px; width: 90%; height: 27px;" .
                              (in_array($state, ['Hot', 'Warm', 'Cold', 'RFQ-Transfer']) ? "color: white;" : "") // Change text color to white for specific statuses
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active'])),
                TextColumn::make('company_name')
                    ->label('COMPANY NAME')
                    ->weight(FontWeight::Bold)
                    ->getStateUsing(fn (Lead $record) => \App\Models\CompanyDetail::find($record->company_name)?->company_name ?? '-'),
                TextColumn::make('from_lead_created')
                    ->label('FROM LEAD CREATED')
                    ->getStateUsing(fn (Lead $record) =>
                        $record->created_at
                            ? Carbon::parse($record->created_at)->diffInDays(Carbon::now()) . ' days'
                            : 'N/A'
                    )
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadStageEnum::tryFrom($state)->getColor() . "; border-radius: 25px; width: 70%;"
                            : '', // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'active', 'inactive'])),
                TextColumn::make('appointment_date')
                    ->label('APPOINTMENT DATE')
                    ->getStateUsing(fn (Lead $record) =>
                        $record->demoAppointment->first()
                            ? sprintf(
                                '%s, %s',
                                Carbon::parse($record->demoAppointment->first()->date)->format('d M Y'),
                                Carbon::parse($record->demoAppointment->first()->start_time)->format('h:i A'),
                                )
                            : '-'
                    )
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'transfer', 'follow_up', 'inactive'])),
                TextColumn::make('day_taken_to_close_deal')
                    ->label('DAYS TAKEN TO CLOSE DEAL')
                    ->getStateUsing(fn (Lead $record) =>
                        $record->lead_status === 'Closed'
                        ? sprintf(
                            '%s days',
                            Carbon::parse($record->created_at)->diffInDays(Carbon::parse($record->updated_at))
                        ) : '-'
                    )
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active', 'transfer', 'follow_up', 'demo'])),
                TextColumn::make('from_new_demo')
                    ->label('FROM NEW DEMO')
                    ->getStateUsing(fn (Lead $record) =>
                        ($days = $record->calculateDaysFromNewDemo()) !== '-'
                            ? $days . ' days'
                            : $days
                    )
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadStageEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadStageEnum::tryFrom($state)->getColor() . "; border-radius: 25px; width: 70%;"
                            : '',  // Fallback if the state is invalid or null
                    ])
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all','transfer', 'demo', 'active', 'inactive'])),
                TextColumn::make('company_size_label')
                    ->label('COMPANY SIZE'),
                TextColumn::make('company_size')
                    ->label('HEADCOUNT'),
            ])
            // ->defaultSort('created_at', 'asc')
            // ->defaultSort('categories', 'New')
            ->defaultSort(function (Builder $query): Builder {
                return $query
                ->orderBy('categories', 'asc') // Sort 'New -> Active -> Inactive' first
                ->orderBy('updated_at', 'desc');
                })
            ->heading(self::getLeadCount() . ' Leads')
            ->actions([
                Tables\Actions\Action::make('updateLeadOwner')
                    ->label(__('Assign to Me'))
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
                            Forms\Components\Placeholder::make('warning')
                                ->content($content)
                                ->hiddenLabel()
                                ->extraAttributes([
                                    'style' => $isDuplicate ? 'color: red; font-weight: bold;' : '',
                                ]),
                        ];
                    })
                    ->color('success')
                    ->size(ActionSize::Small)
                    ->button()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Lead $record) => is_null($record->lead_owner)) // Show only if lead_owner is NULL
                    ->action(function (Lead $record, array $data) {
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
                    }),
                    Tables\Actions\Action::make('resetLead')
                        ->label(__('Reset Lead'))
                        ->color('warning')
                        ->size(ActionSize::Small)
                        ->button()
                        ->visible(fn (Lead $record) => !is_null($record->lead_owner) && in_array(auth()->id(), [12, 11, 4, 5]))
                        ->action(function (Lead $record) {
                            // Reset the specific lead record
                            $record->update([
                                'categories' => 'New',
                                'stage' => 'New',
                                'lead_status' => 'None',
                                'lead_owner' => null,
                                'remark' => null,
                                'follow_up_date' => null,
                                'salesperson' => null,
                                'demo_appointment' => null,
                                'rfq_followup_at' => null,
                                'follow_up_counter' => 0,
                                'follow_up_needed' => 0,
                                'follow_up_count' => 0,
                                'call_attempt' => 0,
                                'done_call' => 0
                            ]);

                            // Delete all related data
                            DB::table('appointments')->where('lead_id', $record->id)->delete();
                            DB::table('system_questions')->where('lead_id', $record->id)->delete();
                            DB::table('bank_details')->where('lead_id', $record->id)->delete();
                            DB::table('activity_logs')->where('subject_id', $record->id)->delete();
                            DB::table('quotations')->where('lead_id', $record->id)->delete();

                            // Send a notification after resetting the lead
                            Notification::make()
                                ->title('Lead Reset Successfully')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record) => route('filament.admin.resources.leads.view', [
                            'record' => Encryptor::encrypt($record->id),
                        ]))
                        ->label('') // Remove the label
                        ->extraAttributes(['class' => 'hidden']),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Get the current user and their role
                $user = auth()->user();
                $roleId = $user->role_id;
                $userName = $user->name;
                $userId = $user->id;

                // Check if the user is an admin (role_id = 1)
                if ($roleId === 2) {
                    $query->where(function ($query) use ($userId) {
                        $query->where('salesperson', $userId)
                              ->orWhere('categories', 'Inactive');
                    });
                }
                // elseif ($roleId === 1) {
                //     // Salespeople (role_id = 2) can see only their records or those without a lead owner
                //     $query->where(function ($query) use ($userName) {
                //         $query->where('lead_owner', $userName)
                //               ->orWhereNull('lead_owner');
                //     });
                // }
            });

    }

    public static function getRelations(): array
    {
        return [
            ActivityLogRelationManager::class,
            DemoAppointmentRelationManager::class,
            QuotationRelationManager::class,
            ProformaInvoiceRelationManager::class
        ];
    }

    public static function getLeadCount(): int
    {
        // Start the Lead query
        $query = Lead::query();

        // Get the current user and their role
        $user = auth()->user();
        $roleId = $user->role_id;
        $userName = $user->name;

        // Apply filters based on role
        if ($roleId === 2) {
            // Role 2: Filter by salesperson or inactive category
            $query->where(function ($query) use ($user) {
                $query->where('salesperson', $user->id)
                      ->orWhere('categories', 'Inactive');
            });
        } elseif ($roleId === 1) {
            // Role 1: Filter by lead owner or null lead owner
            // $query->where(function ($query) use ($userName) {
            //     $query->where('lead_owner', $userName)
            //           ->orWhereNull('lead_owner');
            // });
        }

        // Return the count based on the modified query
        return $query->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLeadRecord::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role_id !== 2;
    }
}
