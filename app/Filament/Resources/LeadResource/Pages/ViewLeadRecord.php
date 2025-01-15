<?php
namespace App\Filament\Resources\LeadResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\HtmlString;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\LeadResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use App\Filament\Resources\LeadResource\RelationManagers\ActivityLogRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\DemoAppointmentRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\LeadDetailRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\LeadSourceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\ProformaInvoiceRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\QuotationRelationManager;
use App\Models\ActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\LeadSource;
use App\Models\SystemQuestion;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;

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

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Lead')->schema([
                    Forms\Components\Grid::make(3) // A three-column grid for overall layout
                    ->schema([
                        // Left-side layout
                        Forms\Components\Grid::make(1) // Nested grid for left side (single column)
                            ->schema([
                                Forms\Components\Section::make('Lead Details')
                                    ->icon('heroicon-o-briefcase')
                                    ->schema([
                                        Forms\Components\Grid::make(2) // Two columns layout for Lead Details
                                            ->schema([
                                                Forms\Components\Placeholder::make('lead_id')
                                                    ->label('Lead ID')
                                                    ->content(fn ($record) => $record->id ?? '-'),
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
                                    Forms\Components\Section::make('Progress')
                                        ->icon('heroicon-o-calendar-days')
                                        ->schema([
                                            Forms\Components\Grid::make(2) // Two columns for Progress
                                                ->schema([
                                                    Forms\Components\Placeholder::make('days_from_lead_created')
                                                        ->label('Total Days from Lead Created')
                                                        ->content(function ($record) {
                                                            $createdDate = $record->created_at;

                                                            if ($createdDate) {
                                                                return $createdDate->diffInDays(now()) . ' days';
                                                            }

                                                            return '-';
                                                        }),
                                                    Forms\Components\Placeholder::make('days_from_new_demo')
                                                        ->label('Total Days from New Demo')
                                                        ->content(fn ($record) => $record->calculateDaysFromNewDemo() . ' days'),
                                                ]),
                                    Forms\Components\Placeholder::make('days_from_rfq_to_inactive')
                                        ->label('Days From RFQ-Follow Up to Inactive')
                                        ->content(function ($record) {
                                            $days = $record->calculateDaysFromRFQTransferToInactive();

                                            return "{$days} days";
                                        }),
                                ]),
                            ])
                            ->columnSpan(2),
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
                                                ->content(fn ($record) => \App\Models\User::find($record->salesperson)?->name ?? 'No Salesperson'),
                                            Actions::make([
                                                Actions\Action::make('edit_sales_in_charge')
                                                    ->label('Edit')
                                                    ->visible(function () {
                                                        // Check if the logged-in user's role_id is 3
                                                        return auth()->user()?->role_id === 3;
                                                    })
                                                    ->action(function ($record, $data) {
                                                        $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';
                                                        $leadOwnerName = \App\Models\User::find($data['lead_owner'])?->name ?? 'Unknown Lead Owner';
                                                        $record->update(['salesperson' => $salespersonName]);
                                                        $record->update(['lead_owner' => $leadOwnerName]);

                                                        $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                                                            ->orderByDesc('created_at')
                                                            ->take(2)
                                                            ->get();

                                                        // Check if at least two logs exist
                                                        if ($latestActivityLogs->count() >= 2) {
                                                            // Update the first activity log
                                                            $latestActivityLogs[0]->update([
                                                                'description' => 'Lead Owner updated by manager: ' . $leadOwnerName,
                                                            ]);

                                                            // Update the second activity log
                                                            $latestActivityLogs[1]->update([
                                                                'description' => 'Salesperson updated by manager: ' . $salespersonName,
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
                                                    ->form([
                                                        Grid::make()
                                                            ->schema([
                                                                Forms\Components\Select::make('position')
                                                                    ->label('Lead Owner')
                                                                    ->options([
                                                                        'sale_admin' => 'Sales Admin',
                                                                    ])
                                                                    ->required(),
                                                                Forms\Components\Select::make('lead_owner')
                                                                    ->options(
                                                                        \App\Models\User::where('role_id', 1) // Filter users with role_id = 1
                                                                            ->pluck('name', 'id')
                                                                    )
                                                                    ->required()
                                                                    ->searchable(), // Allows searching in the dropdown
                                                            ])->columns(2),
                                                        Forms\Components\Select::make('salesperson')
                                                            ->label('Salesperson')
                                                            ->options(
                                                                \App\Models\User::where('role_id', 2) // Filter users with role_id = 2
                                                                    ->pluck('name', 'id')
                                                            )
                                                            ->required()
                                                            ->searchable(), // Allows searching in the dropdown
                                                    ])
                                                    ->modalHeading('Edit Sales In-Charge')
                                                    ->modalDescription('Changing the Lead Owner and Salesperson will allow the new staff
                                                                        to take action on the current and future follow-ups only.')
                                                    ->modalSubmitActionLabel('Save Changes'),
                                            ]),
                                        ]),
                                ])
                                ->columnSpan(1), // Right side spans 1 column
                        ]),
                ]),
                Forms\Components\Tabs\Tab::make('Company')->schema([
                    Forms\Components\Grid::make(3) // A three-column grid for overall layout
                    ->schema([
                        // Left-side layout
                        Forms\Components\Grid::make(1) // Nested grid for left side (single column)
                            ->schema([
                                Forms\Components\Section::make('Company Details')
                                    ->icon('heroicon-o-briefcase')
                                    ->headerActions([
                                        Action::make('edit_company_detail')
                                            ->label('Edit') // Button label
                                            ->modalHeading('Edit Information') // Modal heading
                                            ->modalSubmitActionLabel('Save Changes') // Modal button text
                                            ->form([ // Define the form fields to show in the modal
                                                Forms\Components\TextInput::make('company_name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->company_name ?? '-'),
                                                Forms\Components\TextInput::make('company_address1')
                                                    ->label('Company Address 1')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->company_address1 ?? '-'),
                                                Forms\Components\TextInput::make('company_address2')
                                                    ->label('Company Address 2')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->company_address2 ?? '-'),
                                                Forms\Components\TextInput::make('postcode')
                                                    ->label('Postcode')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->postcode ?? '-'),
                                                Forms\Components\TextInput::make('industry')
                                                    ->label('Industry')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->industry ?? '-'),
                                                Forms\Components\TextInput::make('state')
                                                    ->label('State')
                                                    ->required()
                                                    ->default(fn ($record) => $record->companyDetail->state ?? '-'),
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
                                                Forms\Components\Placeholder::make('company_address1')
                                                    ->label('Company Address 1')
                                                    ->content(fn ($record) => $record->companyDetail->company_address1 ?? '-'),
                                                Forms\Components\Placeholder::make('company_address2')
                                                    ->label('Company Address 2')
                                                    ->content(fn ($record) => $record->companyDetail->company_address2 ?? '-'),
                                                Forms\Components\Placeholder::make('postcode')
                                                    ->label('Postcode')
                                                    ->content(fn ($record) => $record->companyDetail->postcode ?? '-'),
                                                Forms\Components\Placeholder::make('industry')
                                                    ->label('Industry')
                                                    ->content(fn ($record) => $record->companyDetail->industry ?? '-'),
                                                Forms\Components\Placeholder::make('state')
                                                    ->label('State')
                                                    ->content(fn ($record) => $record->companyDetail->state ?? '-'),
                                            ]),
                                    ]),
                                    Forms\Components\Section::make('Person In-Charge')
                                        ->icon('heroicon-o-user')
                                        ->headerActions([
                                            Action::make('edit_person_in_charge')
                                                ->label('Edit') // Button label
                                                ->modalHeading('Edit on Person In-Charge') // Modal heading
                                                ->modalSubmitActionLabel('Save Changes') // Modal button text
                                                ->form([ // Define the form fields to show in the modal
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('Name')
                                                        ->required()
                                                        ->default(fn ($record) => $record->companyDetail->name ?? null),
                                                    Forms\Components\TextInput::make('email')
                                                        ->label('Email')
                                                        ->required()
                                                        ->default(fn ($record) => $record->companyDetail->email ?? null),
                                                    Forms\Components\TextInput::make('position')
                                                        ->label('Position')
                                                        ->required()
                                                        ->default(fn ($record) => $record->companyDetail->position ?? null),
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
                                            Forms\Components\Grid::make(2) // Two columns for Progress
                                                ->schema([
                                                    Forms\Components\Placeholder::make('name')
                                                        ->label('Name')
                                                        ->content(fn ($record) => $record->companyDetail->name ?? '-'),
                                                    Forms\Components\Placeholder::make('contact_no')
                                                        ->label('Contact No.')
                                                        ->content(fn ($record) => $record->companyDetail->contact_no ?? '-'),
                                                ]),
                                            Forms\Components\Placeholder::make('position')
                                                ->label('Position')
                                                ->content(fn ($record) => $record->companyDetail->position ?? '-'),
                                            Forms\Components\Placeholder::make('email')
                                                ->label('Email Address')
                                                ->content(fn ($record) => $record->companyDetail->email ?? '-'),
                                    ]),
                            ])
                            ->columnSpan(2),
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
                                            Actions::make([
                                                Actions\Action::make('edit_status')
                                                    ->label('Edit')
                                                    ->action(function ($record, $data) {
                                                        // Extract selected values
                                                        $newStage = $data['new_stage'];
                                                        $newLeadStatus = $data['new_lead_status'];

                                                        // Update the record based on the selected values
                                                        $record->update([
                                                            'stage' => $newStage === 'Inactive' ? null : $newStage, // Set stage to null if $newStage is 'Inactive'
                                                            'lead_status' => $newLeadStatus,
                                                            'categories' => $newStage === 'Inactive' ? $newStage : $record->categories, // Update categories to newStage if it is 'Inactive'
                                                        ]);

                                                        // Fetch latest activity logs
                                                        $latestActivityLogs = ActivityLog::where('subject_id', $record->id)
                                                            ->orderByDesc('created_at')
                                                            ->take(2)
                                                            ->get();

                                                        if ($latestActivityLogs->count() >= 2) {
                                                            // Update the first activity log
                                                            $latestActivityLogs[0]->update([
                                                                'description' => 'Lead Stage updated to: ' . $newStage,
                                                            ]);

                                                            // Update the second activity log
                                                            $latestActivityLogs[1]->update([
                                                                'description' => 'Lead Status updated to: ' . $newLeadStatus,
                                                            ]);
                                                        } elseif ($latestActivityLogs->count() === 1) {
                                                            // Update the single existing log if only one exists
                                                            $latestActivityLogs[0]->update([
                                                                'description' => 'Lead Stage updated to: ' . $newStage . ' and Lead Status updated to: ' . $newLeadStatus,
                                                            ]);
                                                        }

                                                        activity()
                                                            ->causedBy(auth()->user())
                                                            ->performedOn($record);

                                                        // Notify the user of successful update
                                                        Notification::make()
                                                            ->title('Sales In-Charge Edited Successfully')
                                                            ->success()
                                                            ->send();
                                                    })
                                                    ->form([
                                                        Grid::make() // Three columns layout
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('current_status')
                                                                ->label('Current Status')
                                                                ->content(fn ($record) => ($record->stage ?? $record->categories)
                                                                ? ($record->stage ?? $record->categories) . ' : ' . $record->lead_status
                                                                : '-'),
                                                            Forms\Components\Placeholder::make('arrow') // Arrow in the middle column
                                                                ->content('----------->') // Unicode arrow or any arrow symbol
                                                                ->columnSpan(1)
                                                                ->label(''),
                                                            Forms\Components\Select::make('new_stage')
                                                                ->label('New Stage')
                                                                ->options([
                                                                    'New' => 'New',
                                                                    'Transfer' => 'Transfer',
                                                                    'Demo' => 'Demo',
                                                                    'Follow Up' => 'Follow Up',
                                                                    'Inactive' => 'Inactive',
                                                                ])
                                                                ->required()
                                                                ->reactive(), // Make this field reactive

                                                            Forms\Components\Select::make('new_lead_status')
                                                                ->label('New Lead Status')
                                                                ->options(fn ($get) => match ($get('new_stage')) {
                                                                    'New' => [
                                                                        'None' => 'None',
                                                                    ],
                                                                    'Transfer' => [
                                                                        'New' => 'New',
                                                                        'Under Review' => 'Under Review',
                                                                        'RFQ-Transfer' => 'RFQ-Transfer',
                                                                        'Pending Demo' => 'Pending Demo',
                                                                        'Demo Cancelled' => 'Demo Cancelled',
                                                                    ],
                                                                    'Demo' => [
                                                                        'Demo-Assigned' => 'Demo-Assigned',
                                                                    ],
                                                                    'Follow Up' => [
                                                                        'RFQ-Follow Up' => 'RFQ-Follow Up',
                                                                        'Hot' => 'Hot',
                                                                        'Warm' => 'Warm',
                                                                        'Cold' => 'Cold',
                                                                    ],
                                                                    'Inactive' => [
                                                                        'Junk' => 'Junk',
                                                                        'On Hold' => 'On Hold',
                                                                        'Lost' => 'Lost',
                                                                        'No Response' => 'No Response',
                                                                        'Closed' => 'Closed',
                                                                    ],
                                                                    default => [
                                                                        'None' => 'None',
                                                                        'New' => 'New',
                                                                        'RFQ-Transfer' => 'RFQ-Transfer',
                                                                        'Pending Demo' => 'Pending Demo',
                                                                        'Under Review' => 'Under Review',
                                                                        'Demo Cancelled' => 'Demo Cancelled',
                                                                        'Demo-Assigned' => 'Demo-Assigned',
                                                                        'RFQ-Follow Up' => 'RFQ-Follow Up',
                                                                        'Hot' => 'Hot',
                                                                        'Warm' => 'Warm',
                                                                        'Cold' => 'Cold',
                                                                        'Junk' => 'Junk',
                                                                        'On Hold' => 'On Hold',
                                                                        'Lost' => 'Lost',
                                                                        'No Response' => 'No Response',
                                                                        'Closed' => 'Closed',
                                                                    ],
                                                                })
                                                                ->required(),
                                                        ])->columns(4),

                                                    ])
                                                    ->modalHeading('Edit Status')
                                                    ->modalDescription('You are about to change the status of this lead. Changing the lead
                                                                        status may trigger automated actions based on the new status.')
                                                    ->modalSubmitActionLabel('Confirm')
                                                    ->extraAttributes(function () {
                                                        // Hide the action by applying a CSS class when the user's role_id is 1 or 2
                                                        return auth()->user()->role_id === 1 || auth()->user()->role_id === 2
                                                            ? ['class' => 'hidden']
                                                            : [];
                                                    }),
                                                ]),
                                        ]),
                                ])
                                ->columnSpan(1), // Right side spans 1 column
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
                                                Forms\Components\Placeholder::make('subsidiaries')
                                                    ->label('7. HOW MANY SUBSIDIARIES?')
                                                    ->content(fn ($record) => $record?->systemQuestion?->subsidiaries ?? '-')
                                                    ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                Forms\Components\Placeholder::make('branches')
                                                    ->label('8. HOW MANY BRANCHES?')
                                                    ->content(fn ($record) => $record?->systemQuestion?->branches ?? '-')
                                                    ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                Forms\Components\Placeholder::make('industry')
                                                    ->label('9. WHAT IS YOUR INDUSTRY?')
                                                    ->content(fn ($record) => $record?->systemQuestion?->industry ?? '-')
                                                    ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                                Forms\Components\Placeholder::make('hrdf_contribution')
                                                    ->label('10. DO YOU CONTRIBUTE TO HRDF FUND?')
                                                    ->content(fn ($record) => $record?->systemQuestion?->hrdf_contribution ?? '-')
                                                    ->extraAttributes(['style' => 'padding-left: 15px; font-weight: bold;']),
                                            ])
                                            ->headerActions([
                                                Forms\Components\Actions\Action::make('update')
                                                    ->label('Update')
                                                    ->color('primary')
                                                    ->modalHeading('Update Data')
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
                                                        Forms\Components\TextInput::make('subsidiaries')
                                                            ->label('7. HOW MANY SUBSIDIARIES?')
                                                            ->numeric()
                                                            ->default(fn ($record) => $record?->systemQuestion?->subsidiaries),
                                                        Forms\Components\TextInput::make('branches')
                                                            ->label('8. HOW MANY BRANCHES?')
                                                            ->numeric()
                                                            ->default(fn ($record) => $record?->systemQuestion?->branches),
                                                        Forms\Components\TextInput::make('industry')
                                                            ->label('9. WHAT IS YOUR INDUSTRY?')
                                                            ->default(fn ($record) => $record?->systemQuestion?->industry),
                                                        Forms\Components\Select::make('hrdf_contribution')
                                                            ->label('10. DO YOU CONTRIBUTE TO HRDF FUND?')
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
                                    Forms\Components\Placeholder::make('phase_2_content')
                                        ->content('Content for Phase 2 goes here.'),
                                ]),
                            Forms\Components\Tabs\Tab::make('Phase 3')
                                ->schema([
                                    Forms\Components\Placeholder::make('phase_3_content')
                                        ->content('Content for Phase 3 goes here.'),
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
        ];
    }
}
