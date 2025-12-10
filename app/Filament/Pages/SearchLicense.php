<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SoftwareHandover;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\User;
use App\Services\CategoryService;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use Illuminate\Support\Str;

class SearchLicense extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Search License';
    protected static ?string $title = 'Search License';
    protected static string $view = 'filament.pages.search-license';

    public ?array $data = [];
    public $searchResults = [];
    public $hasSearched = false;
    public $calculatorResult = null;
    public $hasCalculated = false;
    public $projectResults = [];
    public $hasProjectSearched = false;
    public $leadResults = [];
    public $hasLeadSearched = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('License Tools')
                    ->tabs([
                        // Search License Tab
                        Tabs\Tab::make('Search License')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search License Information')
                                    ->description('Enter a company name to search for their license details and invoice information.')
                                    ->schema([
                                        TextInput::make('company_name')
                                            ->label('Company Name')
                                            ->placeholder('Enter company name to search...')
                                            ->live(onBlur: true)
                                            ->extraAlpineAttributes([
                                                'x-on:keydown.enter' => '$wire.searchLicense()'
                                            ])
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('search')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->action('searchLicense')
                                            )
                                    ])
                            ]),

                        // License Range Calculator Tab
                        Tabs\Tab::make('License Range Calculator')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Section::make('License Range Calculator')
                                    ->description('Select a license expiry date to calculate how many months remaining from today.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('expiry_date')
                                                    ->label('License Expiry Date')
                                                    ->placeholder('Select expiry date...')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->extraAlpineAttributes([
                                                        'x-on:keydown.enter' => '$wire.calculateRange()'
                                                    ]),

                                                TextInput::make('result_display')
                                                    ->label('Result')
                                                    ->placeholder('Select date and click submit')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                            ]),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('calculate')
                                                ->label('Submit')
                                                ->icon('heroicon-o-calculator')
                                                ->color('primary')
                                                ->action('calculateRange')
                                        ])
                                    ])
                            ]),

                        // Search Project List Tab
                        Tabs\Tab::make('Search Project List')
                            ->icon('heroicon-o-rectangle-stack')
                            ->schema([
                                Section::make('Search Project Information')
                                    ->description('Enter a company name to search for their software handover projects.')
                                    ->schema([
                                        TextInput::make('project_company_name')
                                            ->label('Company Name')
                                            ->placeholder('Enter company name to search projects...')
                                            ->live(onBlur: true)
                                            ->extraAlpineAttributes([
                                                'x-on:keydown.enter' => '$wire.searchProjects()'
                                            ])
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('searchProjects')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->action('searchProjects')
                                            )
                                    ])
                            ]),

                        // Search Leads Tab
                        Tabs\Tab::make('Search Leads')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Search Leads Information')
                                    ->description('Enter a company name to search for leads and their details.')
                                    ->schema([
                                        TextInput::make('lead_company_name')
                                            ->label('Company Name')
                                            ->placeholder('Enter company name to search leads...')
                                            ->live(onBlur: true)
                                            ->extraAlpineAttributes([
                                                'x-on:keydown.enter' => '$wire.searchLeads()'
                                            ])
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('searchLeads')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->action('searchLeads')
                                            )
                                    ])
                            ])
                    ])
            ])
            ->statePath('data');
    }

    public function searchLicense(): void
    {
        $data = $this->form->getState();

        if (empty($data['company_name'])) {
            Notification::make()
                ->warning()
                ->title('Please enter a company name to search')
                ->send();
            return;
        }

        $this->searchResults = $this->getCompanyLicenseData($data['company_name']);
        $this->hasSearched = true;

        if (empty($this->searchResults)) {
            Notification::make()
                ->warning()
                ->title('No Results Found')
                ->body("No license data found for companies matching '{$data['company_name']}'")
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Search Completed')
                ->body(count($this->searchResults) . ' company(ies) found')
                ->send();
        }
    }

    public function searchProjects(): void
    {
        $data = $this->form->getState();

        if (empty($data['project_company_name'])) {
            Notification::make()
                ->warning()
                ->title('Please enter a company name to search')
                ->send();
            return;
        }

        $this->projectResults = $this->getCompanyProjects($data['project_company_name']);
        $this->hasProjectSearched = true;

        if (empty($this->projectResults)) {
            Notification::make()
                ->warning()
                ->title('No Results Found')
                ->body("No software handover projects found for companies matching '{$data['project_company_name']}'")
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Search Completed')
                ->body(count($this->projectResults) . ' project(s) found')
                ->send();
        }
    }

    public function searchLeads(): void
    {
        $data = $this->form->getState();

        if (empty($data['lead_company_name'])) {
            Notification::make()
                ->warning()
                ->title('Please enter a company name to search')
                ->send();
            return;
        }

        $this->leadResults = $this->getCompanyLeads($data['lead_company_name']);
        $this->hasLeadSearched = true;

        if (empty($this->leadResults)) {
            Notification::make()
                ->warning()
                ->title('No Results Found')
                ->body("No leads found for companies matching '{$data['lead_company_name']}'")
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Search Completed')
                ->body(count($this->leadResults) . ' lead(s) found')
                ->send();
        }
    }

    public function calculateRange(): void
    {
        $data = $this->form->getState();

        if (empty($data['expiry_date'])) {
            Notification::make()
                ->warning()
                ->title('Please select an expiry date')
                ->send();
            return;
        }

        try {
            $expiryDate = Carbon::parse($data['expiry_date']);
            $today = Carbon::today();

            // Calculate exact months difference (with decimal)
            $exactMonths = $today->diffInMonths($expiryDate, false);

            // Get the remaining days after whole months
            $tempDate = $today->copy()->addMonths($exactMonths);
            $remainingDays = $tempDate->diffInDays($expiryDate, false);

            // ✅ Simple rounding logic: if dayDiff >= 15, add 1 month
            $finalMonths = $exactMonths;
            if (abs($remainingDays) >= 15) {
                $finalMonths += ($remainingDays > 0) ? 1 : -1;
            }

            // If the expiry date has passed, show negative months
            if ($expiryDate->lt($today)) {
                $finalMonths = -abs($finalMonths);
                $resultText = abs($finalMonths) . ' months ago (Expired)';
                $statusColor = 'danger';
            } else {
                $resultText = $finalMonths . ' months';
                $statusColor = 'success';
            }

            $this->calculatorResult = $resultText;
            $this->hasCalculated = true;

            // Update the result display field
            $this->form->fill([
                ...$data,
                'result_display' => $resultText
            ]);

            // Calculate exact months for notification
            $daysInMonth = $tempDate->daysInMonth;
            $monthFraction = $remainingDays / $daysInMonth;
            $exactValue = round($exactMonths + $monthFraction, 2);

            Notification::make()
                ->title('Calculation Complete')
                ->body($resultText . ' (Exact: ' . $exactValue . ' months, Day difference: ' . abs($remainingDays) . ' days)')
                ->color($statusColor)
                ->send();

        } catch (\Exception $e) {
            Log::error('Error calculating license range: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Calculation Error')
                ->body('Failed to calculate license range. Please check the date format.')
                ->send();
        }
    }

    public function clearSearch(): void
    {
        $this->searchResults = [];
        $this->hasSearched = false;
        $currentData = $this->form->getState();
        $this->form->fill([
            ...$currentData,
            'company_name' => ''
        ]);
    }

    public function clearProjectSearch(): void
    {
        $this->projectResults = [];
        $this->hasProjectSearched = false;
        $currentData = $this->form->getState();
        $this->form->fill([
            ...$currentData,
            'project_company_name' => ''
        ]);
    }

    public function clearLeadSearch(): void
    {
        $this->leadResults = [];
        $this->hasLeadSearched = false;
        $currentData = $this->form->getState();
        $this->form->fill([
            ...$currentData,
            'lead_company_name' => ''
        ]);
    }

    public function clearCalculator(): void
    {
        $this->calculatorResult = null;
        $this->hasCalculated = false;
        $currentData = $this->form->getState();
        $this->form->fill([
            ...$currentData,
            'expiry_date' => null,
            'result_display' => null
        ]);
    }

    private function generateLeadHtml($leads): string
    {
        if (empty($leads)) {
            return '';
        }

        // Start the HTML with a single table
        $html = '
        <div class="leads-container">
            <style>
                .leads-container {
                    margin: 16px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .leads-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .leads-table th,
                .leads-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #e5e7eb;
                    vertical-align: middle;
                }
                .leads-table th {
                    background-color: #f9fafb;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }
                .leads-table td {
                    font-size: 14px;
                    color: #1f2937;
                }
                .lead-id {
                    font-weight: bold;
                    color: #3b82f6;
                }
                .company-link {
                    color: #338cf0;
                    text-decoration: none;
                    font-weight: 500;
                }
                .company-link:hover {
                    text-decoration: underline;
                }
                .status-badge {
                    padding: 4px 8px;
                    border-radius: 25px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    display: inline-block;
                    text-align: center;
                    min-width: 60px;
                }
                .text-center { text-align: center; }
                .leads-table tbody tr:hover {
                    background-color: #f9fafb;
                }
            </style>

            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Lead Owner</th>
                        <th>Salesperson</th>
                        <th>Created On</th>
                        <th>Company Name</th>
                        <th>Category</th>
                        <th>Lead Status</th>
                    </tr>
                </thead>
                <tbody>';

        // Add each lead as a row in the same table
        foreach ($leads as $leadData) {
            $lead = $leadData['lead'];

            // Get lead owner name
            $leadOwner = $lead->lead_owner ?? '-';

            // Get salesperson name
            $salesperson = $lead->salespersonUser ? $lead->salespersonUser->name : '-';

            // Get company details
            $companyName = $lead->companyDetail->company_name ?? 'N/A';

            // Get encrypted ID for link
            $encryptedId = \App\Classes\Encryptor::encrypt($lead->id);
            $leadLink = url('admin/leads/' . $encryptedId);

            // Get category color
            $categoryColor = '';
            if ($lead->categories) {
                $categoryEnum = LeadCategoriesEnum::tryFrom($lead->categories);
                $categoryColor = $categoryEnum ? $categoryEnum->getColor() : '';
            }

            // Get lead status color
            $leadStatusColor = '';
            $leadStatusTextColor = '';
            if ($lead->lead_status) {
                $leadStatusEnum = LeadStatusEnum::tryFrom($lead->lead_status);
                $leadStatusColor = $leadStatusEnum ? $leadStatusEnum->getColor() : '';
                // Add white text for specific statuses
                $leadStatusTextColor = in_array($lead->lead_status, ['Hot', 'Warm', 'Cold', 'RFQ-Transfer']) ? 'color: white;' : '';
            }

            $html .= '
                    <tr>
                        <td class="lead-id">' . $lead->id . '</td>
                        <td>' . htmlspecialchars($leadOwner) . '</td>
                        <td>' . htmlspecialchars($salesperson) . '</td>
                        <td>' . ($lead->created_at ? Carbon::parse($lead->created_at)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') : 'N/A') . '</td>
                        <td>
                            <a href="' . $leadLink . '" target="_blank" class="company-link">
                                ' . strtoupper(Str::limit($companyName, 30, '...')) . '
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="status-badge" style="background-color: ' . $categoryColor . '; border-radius: 25px; width: 60%; height: 27px;">
                                ' . htmlspecialchars($lead->categories ?? 'N/A') . '
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge" style="background-color: ' . $leadStatusColor . '; border-radius: 25px; width: 90%; height: 27px; ' . $leadStatusTextColor . '">
                                ' . htmlspecialchars($lead->lead_status ?? 'N/A') . '
                            </span>
                        </td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>
        </div>';

        return $html;
    }

    private function getCompanyLeads(string $searchTerm): array
    {
        try {
            // Search for leads by company name
            $leads = Lead::with(['companyDetail', 'salespersonUser'])
                ->whereHas('companyDetail', function ($query) use ($searchTerm) {
                    $query->where('company_name', 'like', "%{$searchTerm}%");
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $results = [];

            foreach ($leads as $lead) {
                $results[] = [
                    'lead' => $lead
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Error searching lead data: ' . $e->getMessage());
            return [];
        }
    }

    private function getCompanyProjects(string $searchTerm): array
    {
        try {
            // Search for projects in software_handovers by company name
            $projects = SoftwareHandover::where('status', 'Completed')
                ->where('company_name', 'like', "%{$searchTerm}%")
                ->orderBy('id', 'desc')
                ->get();

            $results = [];

            foreach ($projects as $project) {
                $results[] = [
                    'project' => $project,
                    'project_html' => $this->generateProjectHtml($project)
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Error searching project data: ' . $e->getMessage());
            return [];
        }
    }

    private function generateProjectHtml(SoftwareHandover $project): string
    {
        // Get company details and links (similar to SoftwareResource)
        $company = CompanyDetail::where('company_name', $project->company_name)->first();
        if (!empty($project->lead_id)) {
            $company = CompanyDetail::where('lead_id', $project->lead_id)->first();
        }

        $companyLink = '';
        if ($company) {
            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);
            $companyLink = url('admin/leads/' . $encryptedId);
        }

        // Format project ID
        $projectId = 'SW_250' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
        if ($project->handover_pdf) {
            $projectId = basename($project->handover_pdf, '.pdf');
        }

        // Calculate total days (similar to SoftwareResource logic)
        $totalDays = '';
        if (!$project->go_live_date) {
            try {
                $completedDate = Carbon::parse($project->completed_at);
                $today = Carbon::now();
                $daysDifference = $completedDate->diffInDays($today);
                $totalDays = $daysDifference . ' ' . Str::plural('day', $daysDifference);
            } catch (\Exception $e) {
                $totalDays = 'Error: ' . $e->getMessage();
            }
        } else {
            try {
                $goLiveDate = Carbon::parse($project->go_live_date);
                $completedDate = Carbon::parse($project->completed_at);
                $daysDifference = $completedDate->diffInDays($goLiveDate);
                $totalDays = $daysDifference . ' ' . Str::plural('day', $daysDifference);
            } catch (\Exception $e) {
                $totalDays = 'Error: ' . $e->getMessage();
            }
        }

        // Get company size using CategoryService
        $categoryService = app(CategoryService::class);
        $companySize = 'N/A';
        if ($project->headcount) {
            $companySize = $categoryService->retrieve($project->headcount);
        }

        $html = '
        <div class="project-container">
            <style>
                .project-container {
                    margin: 16px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .project-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .project-table th,
                .project-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #e5e7eb;
                    vertical-align: middle;
                }
                .project-table th {
                    background-color: #f9fafb;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }
                .project-table td {
                    font-size: 14px;
                    color: #1f2937;
                }
                .project-id {
                    font-weight: bold;
                    color: #3b82f6;
                }
                .company-link {
                    color: #338cf0;
                    text-decoration: none;
                    font-weight: 500;
                }
                .company-link:hover {
                    text-decoration: underline;
                }
                .module-icon {
                    font-size: 1.2rem;
                    margin: 0 2px;
                }
                .module-icon.active {
                    color: green;
                }
                .module-icon.inactive {
                    color: red;
                }
                .status-badge {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .status-open { background: #dbeafe; color: #1e40af; }
                .status-inactive { background: #fee2e2; color: #991b1b; }
                .status-delay { background: #fef3c7; color: #92400e; }
                .status-closed { background: #d1fae5; color: #065f46; }
                .text-center { text-align: center; }
            </style>

            <table class="project-table">
                <thead>
                    <tr>
                        <th>Project ID</th>
                        <th>Company Name</th>
                        <th>Salesperson</th>
                        <th>Implementer</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="project-id">' . htmlspecialchars($projectId) . '</td>
                        <td>' .
                            ($company ?
                                '<a href="' . $companyLink . '" target="_blank" class="company-link">' .
                                strtoupper(Str::limit($project->company_name, 30, '...')) .
                                '</a>'
                                : strtoupper(Str::limit($project->company_name, 30, '...'))) .
                        '</td>
                        <td>' . htmlspecialchars($project->salesperson ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($project->implementer ?? 'N/A') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>';

        return $html;
    }

    // ... (keep all existing methods for license search unchanged)
    private function getCompanyLicenseData(string $searchTerm): array
    {
        try {
            // Search for companies in crm_expiring_license by company name
            $companies = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->select('f_company_id', 'f_company_name')
                ->where('f_company_name', 'like', "%{$searchTerm}%")
                ->whereDate('f_expiry_date', '>=', today())
                ->groupBy('f_company_id', 'f_company_name')
                ->get();

            $results = [];

            foreach ($companies as $company) {
                $licenseData = $this->getLicenseDataForCompany($company->f_company_id);
                $invoiceDetails = $this->getInvoiceDetailsForCompany($company->f_company_id);

                $results[] = [
                    'f_company_id' => $company->f_company_id,
                    'f_company_name' => $company->f_company_name,
                    'license_html' => $this->generateLicenseHtml($licenseData, $invoiceDetails)
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Error searching license data: ' . $e->getMessage());
            return [];
        }
    }

    private function getLicenseDataForCompany($companyId): array
    {
        // Get all license details with dates
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $companyId)
            ->whereDate('f_expiry_date', '>=', today())
            ->get(['f_name', 'f_invoice_no']);

        // ✅ Group licenses by type and collect unique quantities
        $licenseQuantities = [
            'attendance' => [],
            'leave' => [],
            'claim' => [],
            'payroll' => []
        ];

        foreach ($licenses as $license) {
            $licenseName = $license->f_name;
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';

            // Get quantity from crm_invoice_details table
            $invoiceDetail = DB::connection('frontenddb')->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_name', $license->f_name)
                ->first(['f_quantity']);

            $quantity = $invoiceDetail ? (int) $invoiceDetail->f_quantity : 1;

            // Determine license type and base quantity per license
            $licenseType = null;
            $baseQuantity = 0;

            // Attendance licenses
            if (strpos($licenseName, 'TimeTec TA') !== false) {
                $licenseType = 'attendance';
                if (strpos($licenseName, '(10 User License)') !== false) {
                    $baseQuantity = 10;
                } elseif (strpos($licenseName, '(1 User License)') !== false) {
                    $baseQuantity = 1;
                }
            }
            // Leave licenses
            elseif (strpos($licenseName, 'TimeTec Leave') !== false) {
                $licenseType = 'leave';
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Leave License)') !== false) {
                    $baseQuantity = 10;
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Leave License)') !== false) {
                    $baseQuantity = 1;
                }
            }
            // Claim licenses
            elseif (strpos($licenseName, 'TimeTec Claim') !== false) {
                $licenseType = 'claim';
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Claim License)') !== false) {
                    $baseQuantity = 10;
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Claim License)') !== false) {
                    $baseQuantity = 1;
                }
            }
            // Payroll licenses
            elseif (strpos($licenseName, 'TimeTec Payroll') !== false) {
                $licenseType = 'payroll';
                if (strpos($licenseName, '(10 Payroll License)') !== false) {
                    $baseQuantity = 10;
                } elseif (strpos($licenseName, '(1 Payroll License)') !== false) {
                    $baseQuantity = 1;
                }
            }

            if ($licenseType) {
                $totalQuantity = $baseQuantity * $quantity;

                // ✅ Only add if this exact quantity doesn't already exist
                if (!in_array($totalQuantity, $licenseQuantities[$licenseType])) {
                    $licenseQuantities[$licenseType][] = $totalQuantity;
                }
            }
        }

        // ✅ Calculate totals by summing unique quantities for each license type
        $totals = [
            'attendance' => array_sum($licenseQuantities['attendance']),
            'leave' => array_sum($licenseQuantities['leave']),
            'claim' => array_sum($licenseQuantities['claim']),
            'payroll' => array_sum($licenseQuantities['payroll'])
        ];

        return $totals;
    }

    private function getInvoiceDetailsForCompany($companyId): array
    {
        // Check if company has reseller
        $reseller = DB::connection('frontenddb')->table('crm_reseller_link')
            ->select('reseller_name', 'f_rate')
            ->where('f_id', (int) $companyId)
            ->first();

        // Get all license details with f_id included
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $companyId)
            ->whereDate('f_expiry_date', '>=', today())
            ->get([
                'f_id', 'f_name', 'f_unit', 'f_total_amount', 'f_start_date',
                'f_expiry_date', 'f_invoice_no'
            ]);

        $invoiceGroups = [];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';

            // Get invoice details from crm_invoice_details table
            $invoiceDetail = DB::connection('frontenddb')->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_name', $license->f_name)
                ->first(['f_quantity', 'f_unit_price', 'f_billing_cycle', 'f_sales_amount', 'f_total_amount', 'f_gst_amount']);

            $quantity = $invoiceDetail ? $invoiceDetail->f_quantity : $license->f_unit;
            $unitPrice = $invoiceDetail ? $invoiceDetail->f_unit_price : 0;
            $billingCycle = $invoiceDetail ? $invoiceDetail->f_billing_cycle : 0;

            $calculatedAmount = $quantity * $unitPrice * $billingCycle;
            $finalAmount = $calculatedAmount;
            $discountRate = ($reseller && $reseller->f_rate) ? $reseller->f_rate : '0.00';

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'f_id' => $license->f_id,
                    'products' => [],
                    'total_amount' => 0
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_unit' => $quantity,
                'unit_price' => $unitPrice,
                'original_unit_price' => $unitPrice,
                'f_total_amount' => $finalAmount,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $billingCycle,
                'discount' => $discountRate
            ];

            $invoiceGroups[$invoiceNo]['total_amount'] += $finalAmount;
        }

        return $invoiceGroups;
    }

    private function encryptCompanyId($companyId): string
    {
        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($companyId, "AES-128-ECB", $aesKey);
            return base64_encode($encrypted);
        } catch (\Exception $e) {
            Log::error('Company ID encryption failed: ' . $e->getMessage());
            return $companyId;
        }
    }

    private function getProductType($productName): string
    {
        if (strpos($productName, 'TimeTec TA') !== false) {
            return 'ta';
        } elseif (strpos($productName, 'TimeTec Leave') !== false) {
            return 'leave';
        } elseif (strpos($productName, 'TimeTec Claim') !== false) {
            return 'claim';
        } elseif (strpos($productName, 'TimeTec Payroll') !== false) {
            return 'payroll';
        }
        return 'ta';
    }

    private function generateLicenseHtml($licenseData, $invoiceDetails): string
    {
        $html = '
        <div class="license-summary-container">
            <style>
                .license-summary-container {
                    margin: 16px 0;
                }
                .license-summary-table table,
                .invoice-details-table table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 16px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .license-summary-table th,
                .license-summary-table td,
                .invoice-details-table th,
                .invoice-details-table td {
                    padding: 12px 8px;
                    text-align: center;
                    border: 1px solid #e5e7eb;
                    vertical-align: middle;
                }
                .license-summary-table th {
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }
                .license-summary-table td {
                    font-size: 18px;
                    font-weight: 600;
                    color: #1f2937;
                }
                .invoice-details-table th {
                    background-color: #f9fafb !important;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }
                .invoice-details-table td {
                    font-size: 13px;
                    color: #1f2937;
                }
                .module-col {
                    width: 18.75% !important;
                    text-align: center !important;
                    padding-left: 12px !important;
                }
                .headcount-col {
                    width: 6.25% !important;
                    text-align: center !important;
                    font-weight: bold !important;
                }
                .attendance-module {
                    background-color: rgba(34, 197, 94, 0.1) !important;
                    color: rgba(34, 197, 94, 1) !important;
                }
                .attendance-count {
                    background-color: rgba(34, 197, 94, 1) !important;
                    color: white !important;
                }
                .leave-module {
                    background-color: rgba(37, 99, 235, 0.1) !important;
                    color: rgba(37, 99, 235, 1) !important;
                }
                .leave-count {
                    background-color: rgba(37, 99, 235, 1) !important;
                    color: white !important;
                }
                .claim-module {
                    background-color: rgba(124, 58, 237, 0.1) !important;
                    color: rgba(124, 58, 237, 1) !important;
                }
                .claim-count {
                    background-color: rgba(124, 58, 237, 1) !important;
                    color: white !important;
                }
                .payroll-module {
                    background-color: rgba(249, 115, 22, 0.1) !important;
                    color: rgba(249, 115, 22, 1) !important;
                }
                .payroll-count {
                    background-color: rgba(249, 115, 22, 1) !important;
                    color: white !important;
                }
                .invoice-header {
                    background-color: #f3f4f6 !important;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 15px;
                }
                .invoice-group {
                    margin-bottom: 24px;
                }
                .invoice-title {
                    background-color: #e5e7eb;
                    padding: 8px 12px;
                    font-weight: 600;
                    color: #374151;
                    border-radius: 4px;
                    margin-bottom: 8px;
                }
                .invoice-link {
                    color: #2563eb;
                    text-decoration: none;
                    font-weight: 600;
                }
                .invoice-link:hover {
                    color: #1d4ed8;
                    text-decoration: underline;
                }
                .product-row-ta {
                    background-color: rgba(34, 197, 94, 0.1) !important;
                }
                .product-row-leave {
                    background-color: rgba(37, 99, 235, 0.1) !important;
                }
                .product-row-claim {
                    background-color: rgba(124, 58, 237, 0.1) !important;
                }
                .product-row-payroll {
                    background-color: rgba(249, 115, 22, 0.1) !important;
                }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
            </style>

            <!-- License Summary Table -->
            <div class="license-summary-table">
                <table>
                    <thead>
                        <tr>
                            <th class="module-col attendance-module">ATTENDANCE</th>
                            <th class="headcount-col attendance-count">' . $licenseData['attendance'] . '</th>
                            <th class="module-col leave-module">LEAVE</th>
                            <th class="headcount-col leave-count">' . $licenseData['leave'] . '</th>
                            <th class="module-col claim-module">CLAIM</th>
                            <th class="headcount-col claim-count">' . $licenseData['claim'] . '</th>
                            <th class="module-col payroll-module">PAYROLL</th>
                            <th class="headcount-col payroll-count">' . $licenseData['payroll'] . '</th>
                        </tr>
                    </thead>
                </table>
            </div>';

        // Invoice Details Tables
        if (!empty($invoiceDetails)) {
            $html .= '<div class="invoice-details-container">';

            foreach ($invoiceDetails as $invoiceNumber => $invoiceData) {
                $companyFId = $invoiceData['f_id'] ?? null;

                if ($companyFId) {
                    $encryptedFId = $this->encryptCompanyId($companyFId);
                    $invoiceLink = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedFId;
                } else {
                    $invoiceLink = '#';
                }

                $html .= '
                <div class="invoice-group">
                    <div class="invoice-title">Invoice: <a href="' . $invoiceLink . '" target="_blank" class="invoice-link">' . htmlspecialchars($invoiceNumber) . '</a></div>
                    <div class="invoice-details-table">
                        <table>
                            <thead>
                                <tr class="invoice-header">
                                    <th class="text-left">Product Name</th>
                                    <th>Qty</th>
                                    <th class="text-right">Price</th>
                                    <th>Billing Cycle</th>
                                    <th>Start Date</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>';

                foreach ($invoiceData['products'] as $product) {
                    $productType = $this->getProductType($product['f_name']);
                    $html .= '
                                <tr class="product-row-' . $productType . '">
                                    <td style="text-align: left;">' . htmlspecialchars($product['f_name']) . '</td>
                                    <td>' . $product['f_unit'] . '</td>
                                    <td class="text-right">' . number_format($product['unit_price'], 2) . '</td>
                                    <td>' . ($product['billing_cycle'] ?? 'Annual') . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_start_date'])) . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_expiry_date'])) . '</td>
                                </tr>';
                }

                $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
