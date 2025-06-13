<?php

namespace App\Livewire;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

class ImplementerLicense extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getOverdueSoftwareHandovers()
    {
        return SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getOverdueSoftwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_as_migrated')
                        ->label('Complete Migration')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\Section::make('Buffer License')
                                ->schema([
                                    \Filament\Forms\Components\Radio::make('buffer_license_type')
                                        ->hiddenLabel()
                                        ->options([
                                            'predefined' => 'Select from options',
                                            'custom' => 'Enter custom duration',
                                        ])
                                        ->default('predefined')
                                        ->inline()
                                        ->reactive(),

                                    \Filament\Forms\Components\Select::make('buffer_months')
                                        ->label('Duration')
                                        ->options([
                                            '1' => '1 month',
                                            '2' => '2 months',
                                            '3' => '3 months',
                                            '6' => '6 months',
                                            '12' => '12 months',
                                        ])
                                        ->required()
                                        ->default('2')
                                        ->visible(fn (callable $get) => $get('buffer_license_type') === 'predefined'),

                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            \Filament\Forms\Components\TextInput::make('buffer_custom_years')
                                                ->label('Years')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(10)
                                                ->default(0),

                                            \Filament\Forms\Components\TextInput::make('buffer_custom_months')
                                                ->label('Months')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(12)
                                                ->default(2),
                                        ])
                                        ->visible(fn (callable $get) => $get('buffer_license_type') === 'custom'),
                                ])
                                ->compact(),

                            \Filament\Forms\Components\Section::make('Paid License')
                                ->schema([
                                    \Filament\Forms\Components\Radio::make('paid_license_type')
                                        ->hiddenLabel()
                                        ->options([
                                            'predefined' => 'Select from options',
                                            'custom' => 'Enter custom duration',
                                        ])
                                        ->default('predefined')
                                        ->inline()
                                        ->reactive(),

                                    \Filament\Forms\Components\Select::make('paid_license_years')
                                        ->label('Duration')
                                        ->options([
                                            '1' => '1 year',
                                            '2' => '2 years',
                                            '3' => '3 years',
                                            '5' => '5 years',
                                        ])
                                        ->required()
                                        ->default('1')
                                        ->visible(fn (callable $get) => $get('paid_license_type') === 'predefined'),

                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            \Filament\Forms\Components\TextInput::make('paid_custom_years')
                                                ->label('Years')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(10)
                                                ->default(1),

                                            \Filament\Forms\Components\TextInput::make('paid_custom_months')
                                                ->label('Months')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(12)
                                                ->default(0),
                                        ])
                                        ->visible(fn (callable $get) => $get('paid_license_type') === 'custom'),
                                ])
                                ->compact(),
                        ])
                        ->modalHeading("Complete Migration & Generate License Certificate")
                        ->modalDescription('Please configure the license details before marking this handover as migration completed.')
                        ->modalSubmitActionLabel('Complete Migration & Generate Certificate')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function (array $data, SoftwareHandover $record): void {
                            // Get the implementer info
                            $implementer = \App\Models\User::where('name', $record->implementer)->first();
                            $implementerEmail = $implementer?->email ?? null;
                            $implementerName = $implementer?->name ?? $record->implementer ?? 'Unknown';

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Calculate license dates
                            $bufferMonths = (int) $data['buffer_months'];
                            $paidLicenseYears = (int) $data['paid_license_years'];
                            $kickOffDate = now();
                            $bufferEndDate = (clone $kickOffDate)->addMonths($bufferMonths);
                            $paidStartDate = (clone $bufferEndDate)->addDay();
                            $paidEndDate = (clone $paidStartDate)->addYears($paidLicenseYears)->subDay();
                            $nextRenewalDate = (clone $paidEndDate)->addDay();

                            // Create a new license certificate record
                            $certificate = \App\Models\LicenseCertificate::create([
                                'company_name' => $companyName,
                                'software_handover_id' => $record->is_dir,
                                'kick_off_date' => $record->kick_off_meeting ?? now(),
                                'buffer_license_start' => $kickOffDate,
                                'buffer_license_end' => $bufferEndDate,
                                'paid_license_start' => $paidStartDate,
                                'paid_license_end' => $paidEndDate,
                                'next_renewal_date' => $nextRenewalDate,
                                'license_years' => $paidLicenseYears,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);

                            // Update the software handover record with license information
                            $record->update([
                                'completed_at' => now(),
                                'data_migrated' => true,
                                'license_certificate_id' => $certificate->id,
                            ]);

                            // Format the handover ID properly
                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            // Send email notification
                            try {
                                $viewName = 'emails.implementer_license_notification';

                                // Create email content structure
                                $emailContent = [
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'implementer' => [
                                        'name' => $implementerName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'licenses' => [
                                        'kickOffDate' => $kickOffDate->format('d M Y'),
                                        'bufferLicense' => [
                                            'start' => $kickOffDate->format('d M Y'),
                                            'end' => $bufferEndDate->format('d M Y'),
                                            'duration' => $bufferMonths . ' month' . ($bufferMonths > 1 ? 's' : '')
                                        ],
                                        'paidLicense' => [
                                            'start' => $paidStartDate->format('d M Y'),
                                            'end' => $paidEndDate->format('d M Y'),
                                            'duration' => $paidLicenseYears . ' year' . ($paidLicenseYears > 1 ? 's' : '')
                                        ],
                                        'nextRenewal' => $nextRenewalDate->format('d M Y')
                                    ],
                                ];

                                // Initialize recipients array
                                $recipients = [];

                                // Add implementer email if valid
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // Always include adminx
                                $recipients[] = 'admin.timetec.hr@timeteccloud.com';

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $certificateId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("LICENSE CERTIFICATE | TIMETEC HR | {$certificateId} | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("Data migration completion & license certification email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for software handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('Migration completed with license certificate')
                                ->success()
                                ->body("Data migration completed and license certificate {$certificateId} generated successfully.")
                                ->send();
                        })
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.implementer-license');
    }
}
