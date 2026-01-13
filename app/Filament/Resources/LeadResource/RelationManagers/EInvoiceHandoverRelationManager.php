<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Models\EInvoiceHandover;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EInvoiceHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'eInvoiceHandover';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    /**
     * Check if a subsidiary has complete information
     */
    private function isSubsidiaryComplete($subsidiary): bool
    {
        // Check all required fields for subsidiary
        $requiredFields = [
            'company_name',
            'company_address1',
            'postcode',
            'city',
            'state',
            'country',
            'currency',
            'business_type',
            'business_category',
            'billing_category',
            'name', // HR contact name
            'contact_number',
            'email',
            'position',
            'finance_person_name',
            'finance_person_email',
            'finance_person_contact',
            'finance_person_position',
        ];

        foreach ($requiredFields as $field) {
            if (empty($subsidiary->{$field})) {
                return false;
            }
        }



        return true;
    }

    /**
     * Check if all required E-Invoice data is filled
     */
    private function isEInvoiceDataComplete(): bool
    {
        $lead = $this->getOwnerRecord();
        $eInvoiceDetail = $lead->eInvoiceDetail;

        if (!$eInvoiceDetail) {
            return false;
        }

        // Check all required Company Information fields
        if (empty($eInvoiceDetail->company_name) ||
            empty($eInvoiceDetail->business_register_number) ||
            // empty($eInvoiceDetail->tax_identification_number) ||
            empty($eInvoiceDetail->msic_code)) {
            return false;
        }



        // Check all required Address Information fields
        if (empty($eInvoiceDetail->address_1) ||
            empty($eInvoiceDetail->postcode) ||
            empty($eInvoiceDetail->city) ||
            empty($eInvoiceDetail->state) ||
            empty($eInvoiceDetail->country)) {
            return false;
        }

        // Check all required Business Information fields
        if (empty($eInvoiceDetail->currency) ||
            empty($eInvoiceDetail->business_type) ||
            empty($eInvoiceDetail->business_category) ||
            empty($eInvoiceDetail->billing_category)) {
            return false;
        }

        // Check all required Finance Details fields
        if (empty($eInvoiceDetail->finance_person_name) ||
            empty($eInvoiceDetail->finance_person_email) ||
            empty($eInvoiceDetail->finance_person_contact) ||
            empty($eInvoiceDetail->finance_person_position)) {
            return false;
        }

        // Check HR Details (from company detail)
        $companyDetail = $lead->companyDetail;
        if (!$companyDetail) {
            return false;
        }

        if (empty($companyDetail->name) ||
            empty($companyDetail->email) ||
            empty($companyDetail->contact_no) ||
            empty($companyDetail->position)) {
            return false;
        }

        return true;
    }

    /**
     * Get missing required fields for E-Invoice data
     */
    private function getMissingFields(): array
    {
        $lead = $this->getOwnerRecord();
        $eInvoiceDetail = $lead->eInvoiceDetail;
        $companyDetail = $lead->companyDetail;
        $missing = [];

        if (!$eInvoiceDetail) {
            return [
                'E-Invoice Details' => ['All E-Invoice details are missing. Please fill out the E-Invoice Details section.']
            ];
        }

        // Check Company Information fields
        $companyMissing = [];
        if (empty($eInvoiceDetail->company_name)) $companyMissing[] = 'Company Name';
        if (empty($eInvoiceDetail->business_register_number)) {
            $companyMissing[] = 'Business Register Number';
        }
        // if (empty($eInvoiceDetail->tax_identification_number)) $companyMissing[] = 'Tax Identification Number';
        if (empty($eInvoiceDetail->msic_code)) $companyMissing[] = 'MSIC Code';

        if (!empty($companyMissing)) {
            $missing['Company Information'] = $companyMissing;
        }

        // Check Address Information fields
        $addressMissing = [];
        if (empty($eInvoiceDetail->address_1)) $addressMissing[] = 'Address 1';
        if (empty($eInvoiceDetail->postcode)) $addressMissing[] = 'Postcode';
        if (empty($eInvoiceDetail->city)) $addressMissing[] = 'City';
        if (empty($eInvoiceDetail->state)) $addressMissing[] = 'State';
        if (empty($eInvoiceDetail->country)) $addressMissing[] = 'Country';

        if (!empty($addressMissing)) {
            $missing['Address Information'] = $addressMissing;
        }

        // Check Business Information fields
        $businessMissing = [];
        if (empty($eInvoiceDetail->currency)) $businessMissing[] = 'Currency';
        if (empty($eInvoiceDetail->business_type)) $businessMissing[] = 'Business Type';
        if (empty($eInvoiceDetail->business_category)) $businessMissing[] = 'Business Category';
        if (empty($eInvoiceDetail->billing_category)) $businessMissing[] = 'Billing Category';

        if (!empty($businessMissing)) {
            $missing['Business Information'] = $businessMissing;
        }

        // Check Finance Details fields
        $financeMissing = [];
        if (empty($eInvoiceDetail->finance_person_name)) $financeMissing[] = 'Finance Person Name';
        if (empty($eInvoiceDetail->finance_person_email)) $financeMissing[] = 'Finance Person Email';
        if (empty($eInvoiceDetail->finance_person_contact)) $financeMissing[] = 'Finance Person Contact';
        if (empty($eInvoiceDetail->finance_person_position)) $financeMissing[] = 'Finance Person Position';

        if (!empty($financeMissing)) {
            $missing['Finance Details'] = $financeMissing;
        }

        // Check HR Details (from company detail)
        if (!$companyDetail) {
            $missing['HR Details'] = ['All HR details are missing. Please fill out the HR Details section.'];
        } else {
            $hrMissing = [];
            if (empty($companyDetail->name)) $hrMissing[] = 'Name';
            if (empty($companyDetail->email)) $hrMissing[] = 'Email';
            if (empty($companyDetail->contact_no)) $hrMissing[] = 'Contact No.';
            if (empty($companyDetail->position)) $hrMissing[] = 'Position';

            if (!empty($hrMissing)) {
                $missing['HR Details'] = $hrMissing;
            }
        }

        return $missing;
    }

    public function headerActions(): array
    {
        $actions = [];

        // Show button only when all required data is complete
        if ($this->isEInvoiceDataComplete()) {
            $actions[] = Action::make('AddEInvoiceHandover')
                ->label('Register E-Invoice')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->slideOver()
                ->modalHeading('E-Invoice Handover Registration')
                ->modalWidth(MaxWidth::Large)
                ->modalSubmitActionLabel('Submit')
                ->form([
                    Section::make('Company Selection')
                        ->schema([
                            Grid::make(1)
                                ->schema([
                                    Select::make('company_name')
                                        ->label('Company Name')
                                        ->options(function () {
                                            $lead = $this->getOwnerRecord();
                                            $options = [];

                                            // Add main company if available and not already registered
                                            $companyDetail = $lead->companyDetail;
                                            if ($companyDetail) {
                                                $existingHandover = $lead->eInvoiceHandover()
                                                    ->where('lead_id', $lead->id)
                                                    ->where(function($query) {
                                                        $query->whereNull('subsidiary_id')
                                                              ->orWhere('subsidiary_id', 0);
                                                    })
                                                    ->exists();

                                                if (!$existingHandover) {
                                                    $options["main_{$lead->id}"] = $companyDetail->company_name . ' (Main Company)';
                                                }
                                            }

                                            // Add complete subsidiaries that are not already registered
                                            $subsidiaries = $lead->subsidiaries;
                                            foreach ($subsidiaries as $subsidiary) {
                                                $existingHandover = $lead->eInvoiceHandover()
                                                    ->where('lead_id', $lead->id)
                                                    ->where('subsidiary_id', $subsidiary->id)
                                                    ->exists();

                                                if (!$existingHandover) {
                                                    $isComplete = $this->isSubsidiaryComplete($subsidiary);
                                                    // Only include complete subsidiaries
                                                    if ($isComplete) {
                                                        $options["subsidiary_{$subsidiary->id}"] = $subsidiary->company_name . ' (Subsidiary)';
                                                    }
                                                }
                                            }

                                            return $options;
                                        })
                                        ->required()
                                        ->preload()
                                        ->searchable(),

                                    Select::make('customer_type')
                                        ->label('Customer Type')
                                        ->options([
                                            'New Customer' => 'New Customer',
                                            'Existing Customer' => 'Existing Customer',
                                        ])
                                        ->required(),
                                ])
                        ])
                ])
                ->action(function (array $data): void {
                    $lead = $this->getOwnerRecord();

                    // Parse the selected company identifier (format: "type_id")
                    $selectedIdentifier = $data['company_name'];
                    [$companyType, $companyId] = explode('_', $selectedIdentifier, 2);

                    $subsidiaryId = null;
                    $selectedCompanyName = '';

                    // Get company name based on type
                    if ($companyType === 'main') {
                        $companyDetail = $lead->companyDetail;
                        $selectedCompanyName = $companyDetail->company_name;
                    } else {
                        // Find the subsidiary by ID
                        $subsidiary = $lead->subsidiaries()->find($companyId);
                        if ($subsidiary) {
                            $subsidiaryId = $subsidiary->id;
                            $selectedCompanyName = $subsidiary->company_name;
                        }
                    }

                    // Get salesperson name
                    $salespersonName = auth()->user()->name;
                    if ($lead->salesperson) {
                        $salesperson = User::find($lead->salesperson);
                        $salespersonName = $salesperson ? $salesperson->name : auth()->user()->name;
                    }

                    // Create E-Invoice Handover record
                    $eInvoiceHandoverData = [
                        'lead_id' => $lead->id,
                        'salesperson' => $salespersonName,
                        'company_name' => $selectedCompanyName,
                        'company_type' => $companyType,
                        'customer_type' => $data['customer_type'],
                        'status' => 'New',
                        'created_by' => auth()->id(),
                        'submitted_at' => now(),
                    ];

                    // Add subsidiary_id if this is a subsidiary
                    if ($subsidiaryId) {
                        $eInvoiceHandoverData['subsidiary_id'] = $subsidiaryId;
                    }

                    $eInvoiceHandover = EInvoiceHandover::create($eInvoiceHandoverData);

                    // Update lead einvoice_status to "Pending Finance" only if this is the first handover cycle
                    if (in_array($lead->einvoice_status, [null, 'Pending SalesPerson'])) {
                        $lead->update(['einvoice_status' => 'Pending Finance']);
                    }

                    // Send email notification
                    try {
                        $leadEncrypted = \App\Classes\Encryptor::encrypt($lead->id);
                        $leadUrl = url('admin/leads/' . $leadEncrypted);

                        // Get salesperson email for CC
                        $salespersonEmail = null;
                        if ($lead->salesperson) {
                            $salesperson = User::find($lead->salesperson);
                            $salespersonEmail = $salesperson ? $salesperson->email : null;
                        }

                        $emailData = [
                            'salesperson' => strtoupper($salespersonName),
                            'company_name' => $selectedCompanyName,
                            'lead_url' => $leadUrl,
                        ];

                        Mail::send('emails.einvoice_handover_notification', $emailData, function ($message) use ($salespersonEmail, $eInvoiceHandover, $salespersonName, $selectedCompanyName) {
                            $projectCode = $eInvoiceHandover->project_code;
                            $subject = "{$projectCode} / " . strtoupper($salespersonName) . " / {$selectedCompanyName} / NEW";

                            $message->to('auni@timeteccloud.com')
                                ->cc('faiz@timeteccloud.com')
                                ->subject($subject);

                            if ($salespersonEmail) {
                                $message->cc($salespersonEmail);
                            }
                        });

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send E-Invoice handover notification email", [
                            'error' => $e->getMessage(),
                            'einvoice_handover_id' => $eInvoiceHandover->id ?? null
                        ]);
                    }

                    Notification::make()
                        ->title('E-Invoice Handover Created Successfully')
                        ->success()
                        ->send();
                });
        } else {
            // Show clickable button that displays missing fields when data is incomplete
            $actions[] = Action::make('IncompleteData')
                ->label('Register E-Invoice')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->action(function () {
                    $missingFields = $this->getMissingFields();

                    if (!empty($missingFields)) {
                        $body = "Please complete the following required fields:\n\n";

                        foreach ($missingFields as $section => $fields) {
                            $body .= "**{$section}:**\n";
                            foreach ($fields as $field) {
                                $body .= "• {$field}\n";
                            }
                            $body .= "\n";
                        }

                        Notification::make()
                            ->title('Missing Required Information')
                            ->body($body)
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                });
        }

        return $actions;
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('salesperson')
                    ->label('SalesPerson'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->wrap(),

                TextColumn::make('company_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'In Progress' => new HtmlString('<span style="color: orange;">In Progress</span>'),
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                // Add future actions here if needed
            ])
            ->bulkActions([
                // Add bulk actions if needed
            ]);
    }
}
