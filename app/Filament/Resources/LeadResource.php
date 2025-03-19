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
use App\Models\InvalidLeadReason;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Auth;

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
                        Tabs::make()->tabs([
                            Tabs\Tab::make('Lead')->schema([
                                Grid::make(4) // A three-column grid for overall layout
                                ->schema([
                                    // Left-side layout
                                    Grid::make(2) // Nested grid for left side (single column)
                                        ->schema([
                                            Section::make('Lead Details')
                                                ->icon('heroicon-o-briefcase')
                                                ->headerActions([
                                                    Action::make('edit_person_in_charge')
                                                        ->label('Edit') // Button label
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
                                                        ])
                                                        ->action(function (Lead $lead, array $data) {
                                                            if ($lead) {
                                                                // Update the existing SystemQuestion record
                                                                $lead->update($data);

                                                                Notification::make()
                                                                    ->title('Updated Successfully')
                                                                    ->success()
                                                                    ->send();
                                                            }
                                                        }),
                                                ])
                                                ->schema([
                                                    Grid::make(2) // Two columns layout for Lead Details
                                                        ->schema([
                                                            Placeholder::make('lead_id_placeholder')
                                                                ->label('Lead ID')
                                                                ->content(fn ($record) => $record ? str_pad($record->id, 5, '0', STR_PAD_LEFT) : '-'),
                                                            Placeholder::make('lead_source')
                                                                ->label('Lead Source')
                                                                ->content(fn ($record) => $record->leadSource?->platform ?? '-'),
                                                            Placeholder::make('lead_created_by')
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
                                                            Placeholder::make('lead_created_on')
                                                                ->label('Lead Created On')
                                                                ->content(fn ($record) => $record->created_at?->format('d M Y, H:i') ?? '-'),
                                                            Placeholder::make('company_size')
                                                                ->label('Company Size')
                                                                ->content(fn ($record) => $record->getCompanySizeLabelAttribute() ?? '-'),
                                                            Placeholder::make('headcount')
                                                                ->label('Headcount')
                                                                ->content(fn ($record) => $record->company_size ?? '-'),
                                                        ]),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                        Grid::make(1)
                                            ->schema([
                                                Section::make('Progress')
                                                    ->icon('heroicon-o-calendar-days')
                                                    ->schema([
                                                        Grid::make(1) // Single-column layout for progress
                                                            ->schema([
                                                                Placeholder::make('days_from_lead_created')
                                                                    ->label('Total Days from Lead Created')
                                                                    ->content(function ($record) {
                                                                        $createdDate = $record->created_at;
                                                                        return $createdDate ? $createdDate->diffInDays(now()) . ' days' : '-';
                                                                    }),

                                                                Placeholder::make('days_from_new_demo')
                                                                    ->label('Total Days from New Demo')
                                                                    ->content(fn ($record) => $record->calculateDaysFromNewDemo() . ' days'),

                                                                Placeholder::make('salesperson_assigned_date')
                                                                    ->label('Salesperson Assigned Date')
                                                                    ->content(function ($record) {
                                                                        return $record->salesperson_assigned_date
                                                                            ? \Carbon\Carbon::parse($record->salesperson_assigned_date)->format('d M Y')
                                                                            : '-';
                                                                    }),
                                                            ]),
                                                    ]),
                                            ])
                                            ->columnSpan(1),
                                        Grid::make(1)
                                            ->schema([
                                                Section::make('Sales In-Charge')
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ])
                                                    ->icon('heroicon-o-user')
                                                    ->schema([
                                                        Grid::make(1) // Single column in the right-side section
                                                            ->schema([
                                                                Placeholder::make('lead Owner')
                                                                    ->label('Lead Owner')
                                                                    ->content(fn ($record) =>  $record->lead_owner ?? 'No Lead Owner'),
                                                                Placeholder::make('salesperson')
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
                                                                                                ->options(
                                                                                                    \App\Models\User::where('role_id', 1)
                                                                                                        ->pluck('name', 'id')
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
                                                                                    ->required()
                                                                                    ->searchable(),
                                                                            ]
                                                                        ))
                                                                        ->action(function ($record, $data) {
                                                                            if (!empty($data['salesperson'])) {
                                                                                $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';
                                                                                $record->update(['salesperson' => $data['salesperson'],
                                                                                    'salesperson_assigned_date' => now(),
                                                                                    ]);
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
                            Tabs\Tab::make('Company')->schema([
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
                                                        ->visible(fn (Lead $lead) => !is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
                                                        ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                        ->form([ // Define the form fields to show in the modal
                                                            TextInput::make('company_name')
                                                                ->label('Company Name')
                                                                ->default(fn ($record) => strtoupper($record->companyDetail->company_name ?? '-'))
                                                                ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),
                                                            TextInput::make('company_address1')
                                                                ->label('Company Address 1')
                                                                ->default(fn ($record) => $record->companyDetail->company_address1 ?? '-')
                                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                                                            TextInput::make('company_address2')
                                                                ->label('Company Address 2')
                                                                ->default(fn ($record) => $record->companyDetail->company_address2 ?? '-')
                                                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                                                            Grid::make(3) // Create a 3-column grid
                                                                ->schema([
                                                                    TextInput::make('postcode')
                                                                        ->label('Postcode')
                                                                        ->default(fn ($record) => $record->companyDetail->postcode ?? '-'),

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
                                                                        ->default(fn ($record) => $record->companyDetail->state ?? null)
                                                                        ->searchable()
                                                                        ->preload(),

                                                                    Select::make('industry')
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
                                                    Grid::make(2) // Two columns layout for Lead Details
                                                        ->schema([
                                                            Placeholder::make('company_name')
                                                                ->label('Company Name')
                                                               ->content(fn ($record) => $record->companyDetail->company_name ?? '-'),
                                                            Placeholder::make('postcode')
                                                               ->label('Postcode')
                                                               ->content(fn ($record) => $record->companyDetail->postcode ?? '-'),
                                                            Placeholder::make('company_address1')
                                                                ->label('Company Address 1')
                                                                ->content(fn ($record) => $record->companyDetail->company_address1 ?? '-'),
                                                            Placeholder::make('state')
                                                                ->label('State')
                                                                ->content(fn ($record) => $record->companyDetail->state ?? '-'),
                                                            Placeholder::make('company_address2')
                                                                ->label('Company Address 2')
                                                                ->content(fn ($record) => $record->companyDetail->company_address2 ?? '-'),
                                                            Placeholder::make('industry')
                                                                ->label('Industry')
                                                                ->content(fn ($record) => $record->companyDetail->industry ?? '-'),
                                                        ]),
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
                                                            ->visible(fn (Lead $lead) => !is_null($lead->lead_owner) || (is_null($lead->lead_owner) && !is_null($lead->salesperson)))
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
                                                        Placeholder::make('name')
                                                            ->label('Name')
                                                            ->content(fn ($record) => $record->companyDetail->name ?? $record->name),
                                                        Placeholder::make('contact_no')
                                                            ->label('Contact No.')
                                                            ->content(fn ($record) => $record->companyDetail->contact_no ?? $record->phone),
                                                        Placeholder::make('email')
                                                            ->label('Email Address')
                                                            ->content(fn ($record) => $record->companyDetail->email ?? $record->email),
                                                    ]),
                                                ])->columnSpan(1),
                                        Grid::make(1) // Nested grid for left side (single column)
                                            ->schema([
                                                Section::make('Status')
                                                    ->icon('heroicon-o-information-circle')
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ])
                                                    ->schema([
                                                        Grid::make(1) // Single column in the right-side section
                                                            ->schema([
                                                                Placeholder::make('deal_amount')
                                                                    ->label('Deal Amount')
                                                                    ->content(fn ($record) => $record ? 'RM ' . number_format($record->deal_amount, 2) : 'RM 0.00'),
                                                                Placeholder::make('status')
                                                                    ->label('Status')
                                                                    ->content(fn ($record) => ($record->stage ?? $record->categories)
                                                                    ? ($record->stage ?? $record->categories) . ' : ' . $record->lead_status
                                                                    : '-'),
                                                                Actions::make([
                                                                    Action::make('archive')
                                                                        ->label(__('Edit'))
                                                                        ->modalHeading('Mark Lead as Inactive')
                                                                        ->form([
                                                                            Placeholder::make('')
                                                                                ->content(__('Please select the reason to mark this lead as inactive and add any relevant remarks.')),

                                                                            Select::make('status')
                                                                                ->label('INACTIVE STATUS')
                                                                                ->options([
                                                                                    'On Hold' => 'On Hold',
                                                                                    'Junk' => 'Junk',
                                                                                    'Lost' => 'Lost',
                                                                                    'Closed' => 'Closed',
                                                                                ])
                                                                                ->default('On Hold')
                                                                                ->required()
                                                                                ->reactive(),

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

                                                                            // Deal Amount Field - Visible only when status is Closed
                                                                            TextInput::make('deal_amount')
                                                                                ->label('Close Deal Amount')
                                                                                ->numeric()
                                                                                ->hidden(fn (callable $get) => $get('status') !== 'Closed')
                                                                                ->required(fn (callable $get) => $get('status') === 'Closed'),

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
                                                                            ];

                                                                            // If lead is closed, update deal amount
                                                                            if ($data['status'] === 'Closed') {
                                                                                $updateData['deal_amount'] = $data['deal_amount'] ?? null;
                                                                            } else {
                                                                                // If not closed, update reason
                                                                                $updateData['reason'] = InvalidLeadReason::find($data['reason'])?->reason ?? 'Unknown Reason';
                                                                            }

                                                                            $lead->update($updateData);

                                                                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                                                                ->orderByDesc('created_at')
                                                                                ->first();

                                                                            if ($latestActivityLog) {
                                                                                $latestActivityLog->update([
                                                                                    'description' => 'Marked as ' . $statusLabel . ': ' . ($updateData['reason'] ?? 'No reason'),
                                                                                ]);

                                                                                activity()
                                                                                    ->causedBy(auth()->user())
                                                                                    ->performedOn($lead)
                                                                                    ->log('Lead marked as inactive.');
                                                                            }

                                                                            Notification::make()
                                                                                ->title('Lead Archived')
                                                                                ->success()
                                                                                ->body('You have successfully marked the lead as inactive.')
                                                                                ->send();
                                                                        }),
                                                                ])
                                                            ]),
                                                    ])
                                            ])->columnSpan(1),
                                        ]),
                            ]),
                            Tab::make('System')
                                ->schema([
                                    Tabs::make('Phases')
                                        ->tabs([
                                            Tabs\Tab::make('Phase 1')
                                                ->schema([
                                                    Section::make('Phase 1')
                                                        ->description(fn ($record) =>
                                                            $record && $record->systemQuestion
                                                                ? 'Updated by ' . ($record->systemQuestion->causer_name ?? 'Unknown') . ' on ' .
                                                                ($record->systemQuestion->updated_at?->format('F j, Y, g:i A') ?? 'N/A')
                                                                : null
                                                        )
                                                        ->schema([
                                                            Placeholder::make('modules')
                                                                ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->modules ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('existing_system')
                                                                ->label('2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->existing_system ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('usage_duration')
                                                                ->label('3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->usage_duration ?? '-')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->existing_system ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('expired_date')
                                                                ->label('4. WHEN IS THE EXPIRED DATE?')
                                                                ->content(fn ($record) => $record?->systemQuestion?->expired_date
                                                                    ? \Carbon\Carbon::createFromFormat('Y-m-d', $record->systemQuestion->expired_date)->format('d/m/Y')
                                                                    : '-')
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('reason_for_change')
                                                                ->label('5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->reason_for_change ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('staff_count')
                                                                ->label('6. HOW MANY STAFF DO YOU HAVE?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->staff_count ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('hrdf_contribution')
                                                                ->label('7. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->hrdf_contribution ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('additional')
                                                                ->label('8. ADDITIONAL QUESTIONS?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestion?->additional ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Actions\Action::make('update')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->latest()
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false;
                                                                    }

                                                                    if ($demoAppointment->status !== 'Done') {
                                                                        return true;
                                                                    }

                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Textarea::make('modules')
                                                                        ->label('1. WHICH MODULE THAT YOU ARE LOOKING FOR?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->modules),
                                                                    Textarea::make('existing_system')
                                                                        ->label('2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->existing_system),
                                                                    Textarea::make('usage_duration')
                                                                        ->label('3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->usage_duration),
                                                                    DatePicker::make('expired_date')
                                                                        ->label('4. WHEN IS THE EXPIRED DATE?')
                                                                        ->default(fn ($record) => $record?->systemQuestion?->expired_date),
                                                                    Textarea::make('reason_for_change')
                                                                        ->label('5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?')
                                                                        ->autosize()
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->reason_for_change)
                                                                        ->rows(3),
                                                                    Textarea::make('staff_count')
                                                                        ->label('6. HOW MANY STAFF DO YOU HAVE?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->staff_count),
                                                                    Select::make('hrdf_contribution')
                                                                        ->label('7. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                                        ->options([
                                                                            'Yes' => 'Yes',
                                                                            'No' => 'No',
                                                                        ])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->hrdf_contribution),
                                                                    Textarea::make('additional')
                                                                        ->label('8. ADDITIONAL QUESTIONS?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestion?->additional),
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
                                            Tabs\Tab::make('Phase 2')
                                                ->schema([
                                                    Section::make('Phase 2')
                                                        ->description(function ($record) {
                                                            if ($record && $record->systemQuestionPhase2 && !empty($record->systemQuestionPhase2->updated_at)) {
                                                                return 'Updated by ' . ($record->systemQuestionPhase2->causer_name ?? 'Unknown') . ' on ' .
                                                                    \Carbon\Carbon::parse($record->systemQuestionPhase2->updated_at)->format('F j, Y, g:i A');
                                                            }

                                                            return null; // Return null if no update exists
                                                        })
                                                        ->schema([
                                                            Placeholder::make('support')
                                                                ->label('1.  PROSPECT QUESTION – NEED TO REFER SUPPORT TEAM.')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase2?->support ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('product')
                                                                ->label('2. PROSPECT CUSTOMIZATION – NEED TO REFER PRODUCT TEAM.')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase2?->product ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('additional')
                                                                ->label('3. ADDITIONAL QUESTIONS?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase2?->additional ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Actions\Action::make('update_phase2')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->latest()
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false;
                                                                    }

                                                                    if ($demoAppointment->status !== 'Done') {
                                                                        return true;
                                                                    }

                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Textarea::make('support')
                                                                        ->label('1.  PROSPECT QUESTION – NEED TO REFER SUPPORT TEAM.')
                                                                        ->autosize()
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase2?->support)
                                                                        ->rows(3),
                                                                    Textarea::make('product')
                                                                        ->label('2. PROSPECT CUSTOMIZATION – NEED TO REFER PRODUCT TEAM.')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase2?->product),
                                                                    Textarea::make('additional')
                                                                        ->label('3. ADDITIONAL QUESTIONS?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase2?->additional),
                                                                ])
                                                                ->action(function (Lead $lead, array $data) {
                                                                    // Retrieve the current lead's systemQuestion
                                                                    $record = $lead->systemQuestionPhase2;

                                                                    if ($record) {
                                                                        // Include causer_id in the data
                                                                        $data['causer_name'] = auth()->user()->name;

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
                                                                        $lead->systemQuestionPhase2()->create($data);

                                                                        Notification::make()
                                                                            ->title('Created Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    }
                                                                }),
                                                        ])
                                                ]),
                                            Tabs\Tab::make('Phase 3')
                                                ->schema([
                                                    Section::make('Phase 3')
                                                        ->description(function ($record) {
                                                            if ($record && $record->systemQuestionPhase3 && !empty($record->systemQuestionPhase3->updated_at)) {
                                                                return 'Updated by ' . ($record->systemQuestionPhase3->causer_name ?? 'Unknown') . ' on ' .
                                                                    \Carbon\Carbon::parse($record->systemQuestionPhase3->updated_at)->format('F j, Y, g:i A');
                                                            }

                                                            return null; // Return null if no update exists
                                                        })
                                                        ->schema([
                                                            Placeholder::make('percentage')
                                                                ->label('1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase3?->percentage ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('vendor')
                                                                ->label('2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase3?->vendor ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('plan')
                                                                ->label('3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase3?->plan ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('finalise')
                                                                ->label('4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase3?->finalise ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                            Placeholder::make('additional')
                                                                ->label('5. ADDITIONAL QUESTIONS?')
                                                                ->content(function ($record) {
                                                                    $content = $record?->systemQuestionPhase3?->additional ?? '-';
                                                                    return new HtmlString(
                                                                        '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
                                                                        . nl2br(e($content))
                                                                        . '</div>'
                                                                    );
                                                                })
                                                                ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                        ])
                                                        ->headerActions([
                                                            Actions\Action::make('update_phase3')
                                                                ->label('Update')
                                                                ->color('primary')
                                                                ->modalHeading('Update Data')
                                                                ->visible(function ($record) {
                                                                    $demoAppointment = $record->demoAppointment()
                                                                        ->latest()
                                                                        ->first();

                                                                    if (!$demoAppointment) {
                                                                        return false;
                                                                    }

                                                                    if ($demoAppointment->status !== 'Done') {
                                                                        return true;
                                                                    }

                                                                    return $demoAppointment->updated_at->diffInHours(now()) <= 48;
                                                                })
                                                                ->form([
                                                                    Textarea::make('percentage')
                                                                        ->label('1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?')
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->percentage),
                                                                    Textarea::make('vendor')
                                                                        ->label('2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->vendor),
                                                                    Textarea::make('plan')
                                                                        ->label('3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->plan),
                                                                    Textarea::make('finalise')
                                                                        ->label('4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->finalise),
                                                                    Textarea::make('additional')
                                                                        ->label('5. ADDITIONAL QUESTIONS?')
                                                                        ->autosize()
                                                                        ->rows(3)
                                                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                                        ->default(fn ($record) => $record?->systemQuestionPhase3?->additional),
                                                                ])
                                                                ->action(function (Lead $lead, array $data) {
                                                                    // Retrieve the current record's systemQuestionPhase3 relation
                                                                    $record = $lead->systemQuestionPhase3;

                                                                    if ($record) {
                                                                        // Add causer_name and updated_at to the data array
                                                                        $data['causer_name'] = auth()->user()->name;
                                                                        $data['updated_at'] = now();

                                                                        // Update the existing record properly
                                                                        $record->update($data);

                                                                        Notification::make()
                                                                            ->title('Updated Successfully')
                                                                            ->success()
                                                                            ->send();
                                                                    } else {
                                                                        // If no record exists, add causer_name to the data array
                                                                        $data['causer_name'] = auth()->user()->name;

                                                                        // Create a new record via the relationship
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
                            Tabs\Tab::make('Refer & Earn')
                            ->schema([
                                Grid::make(1) // Main grid for all sections
                                    ->schema([
                                        // First row: Referral Details (From and Refer To)
                                        Grid::make(2) // Three columns layout: From, Arrow, Refer To
                                            ->schema([
                                                // From Section
                                                Section::make('From')
                                                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                                                    ->schema([
                                                        Grid::make(2) // Create a grid with two columns
                                                        ->schema([
                                                            Placeholder::make('from_company')
                                                                ->label('COMPANY')
                                                                ->content(fn ($record) => $record?->referralDetail?->company ?? '-'),
                                                            Placeholder::make('from_remark')
                                                                ->label('REMARK')
                                                                ->content(fn ($record) => $record?->referralDetail?->remark ?? '-'),
                                                        ]),
                                                        Placeholder::make('from_name')
                                                            ->label('NAME')
                                                            ->content(fn ($record) => $record?->referralDetail?->name ?? '-'),
                                                        Placeholder::make('from_email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record?->referralDetail?->email ?? '-'),
                                                        Placeholder::make('from_contact')
                                                            ->label('CONTACT NO.')
                                                            ->content(fn ($record) => $record?->referralDetail?->contact_no ?? '-'),
                                                    ])
                                                    ->columnSpan(1)
                                                    ->extraAttributes([
                                                        'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                                    ]),
                                                // // Arrow in the center
                                                // Placeholder::make('arrow')
                                                //     ->content('→') // Arrow symbol
                                                //     ->columnSpan(1)
                                                //     ->label(''),

                                                // Refer To Section
                                                Section::make('Refer to')
                                                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                                                    ->schema([
                                                        Placeholder::make('to_company')
                                                            ->label('COMPANY')
                                                            ->content(fn ($record) => $record->companyDetail->company_name ?? null),
                                                        Placeholder::make('to_name')
                                                            ->label('NAME')
                                                            ->content(fn ($record) => $record->name ?? null),
                                                        Placeholder::make('to_email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record->email ?? null),
                                                        Placeholder::make('to_contact')
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
                                        Section::make('Bank Details')
                                            ->icon('heroicon-o-chat-bubble-left')
                                            ->extraAttributes([
                                                'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                            ])
                                            ->headerActions([
                                                Action::make('Edit')
                                                    ->label('Edit') // Button label
                                                    ->modalHeading('Edit Information') // Modal heading
                                                    ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                    ->hidden()
                                                    ->form([ // Define the form fields to show in the modal
                                                        TextInput::make('full_name')
                                                            ->label('FULL NAME')
                                                            ->default(fn ($record) => $record?->bankDetail?->full_name ?? null),
                                                        TextInput::make('ic')
                                                            ->label('IC NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->ic ?? null),
                                                        TextInput::make('tin')
                                                            ->label('TIN NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->tin ?? null),
                                                        TextInput::make('bank_name')
                                                            ->label('BANK NAME')
                                                            ->default(fn ($record) => $record?->bankDetail?->bank_name ?? null),
                                                        TextInput::make('bank_account_no')
                                                            ->label('BANK ACCOUNT NO.')
                                                            ->default(fn ($record) => $record?->bankDetail?->bank_account_no ?? null),
                                                        TextInput::make('contact_no')
                                                            ->label('CONTACT NUMBER')
                                                            ->default(fn ($record) => $record?->bankDetail?->contact_no ?? null),
                                                        TextInput::make('email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->default(fn ($record) => $record?->bankDetail?->email ?? null),
                                                        Select::make('referral_payment_status')
                                                            ->label('REFERRAL PAYMENT STATUS')
                                                            ->default(fn ($record) => $record?->bankDetail?->payment_referral_status ?? null)
                                                            ->options([
                                                                'PENDING' => 'Pending',
                                                                'PAID' => 'Paid',
                                                                'PROCESSING' => 'Processing',
                                                            ]),
                                                        TextInput::make('remark')
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
                                                Grid::make(4) // Four columns layout
                                                    ->schema([
                                                        Placeholder::make('full_name')
                                                            ->label('FULL NAME')
                                                            ->content(fn ($record) => $record?->bankDetail?->full_name ?? '-'),
                                                        Placeholder::make('bank_name')
                                                            ->label('BANK NAME')
                                                            ->content(fn ($record) => $record?->bankDetail?->bank_name ?? '-'),
                                                        Placeholder::make('contact_no')
                                                            ->label('CONTACT NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->contact_no ?? '-'),
                                                        Placeholder::make('referral_payment_status')
                                                            ->label('REFERRAL PAYMENT STATUS')
                                                            ->content(fn ($record) => $record?->bankDetail?->referral_payment_status ?? '-'),
                                                        Placeholder::make('ic_no')
                                                            ->label('IC NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->ic ?? '-'),
                                                        Placeholder::make('bank_account_no')
                                                            ->label('BANK ACCOUNT NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->bank_account_no ?? '-'),
                                                        Placeholder::make('email')
                                                            ->label('EMAIL ADDRESS')
                                                            ->content(fn ($record) => $record?->bankDetail?->email ?? '-'),
                                                        Placeholder::make('remark')
                                                            ->label('REMARK')
                                                            ->content(fn ($record) => $record?->bankDetail?->remark ?? '-'),
                                                        Placeholder::make('tin')
                                                            ->label('TIN NO.')
                                                            ->content(fn ($record) => $record?->bankDetail?->tin ?? '-'),
                                                    ]),
                                            ]),
                                    ]),
                                ]),
                            Tabs\Tab::make('Appointment')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager::class,
                                ),
                            ]),
                            Tabs\Tab::make('Prospect Follow Up')->schema([
                                \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                    ->manager(\App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager::class,
                                ),
                            ]),
                            Tabs\Tab::make('Quotation')->schema([
                                // \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                //     ->manager(\App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager::class,
                                // ),
                            ]),
                            Tabs\Tab::make('Proforma Invoice')->schema([
                                // \Njxqlus\Filament\Components\Forms\RelationManager::make()
                                //     ->manager(\App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager::class,
                                // ),
                            ]),
                            Tabs\Tab::make('Invoice')->schema([

                            ]),
                            Tabs\Tab::make('Debtor Follow Up')->schema([

                            ]),
                            Tabs\Tab::make('Software Handover')->schema([

                            ]),
                            Tabs\Tab::make('Hardware Handover')->schema([

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
                    ->placeholder('Select Lead Owner'),

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
                    ->options(function ($livewire) {
                        // Default options from Enum
                        $defaultOptions = collect(LeadStageEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray();

                        // If activeTab is "transfer", set specific options
                        if ($livewire->activeTab === 'active') {
                            return [
                                'Transfer' => 'TRANSFER',
                                'Demo' => 'DEMO',
                                'Follow Up' => 'FOLLOW UP',
                            ];
                        }

                        return $defaultOptions;
                    })
                    ->placeholder('Select Stage')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'transfer', 'demo', 'follow_up', 'inactive'])),


                // Filter for Lead Status
                SelectFilter::make('lead_status')
                    ->label('')
                    ->multiple()
                    ->options(function ($livewire) {
                        // Default options from Enum
                        $defaultOptions = collect(LeadStatusEnum::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                            ->toArray();

                        // If activeTab is "transfer", use specific options
                        if ($livewire->activeTab === 'transfer') {
                            return [
                                'New' => 'NEW',
                                'RFQ-TRANSFER' => 'RFQ TRANSFER',
                                'Pending Demo' => 'PENDING DEMO',
                                'Demo Cancelled' => 'DEMO CANCELLED',
                                'Under Review' => 'UNDER REVIEW',
                            ];
                        }

                        if ($livewire->activeTab === 'demo') {
                            return [
                                'Demo-Assigned' => 'DEMO ASSIGNED',
                                'Demo Cancelled' => 'DEMO CANCELLED',
                            ];
                        }
                        return $defaultOptions;
                    })
                    ->placeholder('Select Lead Status')
                    ->hidden(fn ($livewire) => in_array($livewire->activeTab, ['all', 'active'])),

                Filter::make('company_name')
                    ->form([
                        TextInput::make('company_name')
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
                    ->wrap()
                    ->label('COMPANY NAME')
                    ->weight(FontWeight::Bold)
                    ->getStateUsing(fn (Lead $record) => $record->companyDetail?->company_name ?? '-'),
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
                            Placeholder::make('warning')
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
                    ->visible(fn (Lead $record) => is_null($record->lead_owner) && auth()->user()->role_id !== 2
                                                     && is_null($record->salesperson))
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
                        ->visible(fn (Lead $record) => Auth::user()->role_id == 3 && $record->id == 7197)
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
                                'salesperson_assigned_date' => null,
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
                    $query->where('salesperson', $userId)
                          ->whereIn('categories', ['Inactive', 'Active', 'New']); // Add more statuses if needed
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

    // public static function canCreate(): bool
    // {
    //     return auth()->user()->role_id !== 2;
    // }
}
