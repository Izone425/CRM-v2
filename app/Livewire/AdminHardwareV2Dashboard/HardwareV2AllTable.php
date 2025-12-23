<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHardwareV2Dashboard/HardwareV2NewTable.php

namespace App\Livewire\AdminHardwareV2Dashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\Lead;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Livewire\Attributes\On;

class HardwareV2AllTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    protected static ?int $indexRepeater3 = 0;
    protected static ?int $indexRepeater4 = 0;

    public $selectedUser;
    public $lastRefreshTime;
    public $currentDashboard;

    public function mount($currentDashboard = null)
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->currentDashboard = $currentDashboard ?? 'HardwareAdminV2';
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-HardwareHandoverV2-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);
        $this->resetTable();
    }

    public function getNewHardwareHandovers()
    {
        $query = HardwareHandoverV2::query();

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Pending Migration' THEN 3
            WHEN status = 'Pending Stock' THEN 4
            ELSE 5
        END")
        ->orderBy('created_at', 'desc');

        return $query;
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query();

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('invoice_type')
                    ->label('Filter by Invoice Type')
                    ->options([
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                    ])
                    ->placeholder('All Invoice Types')
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Pending: Courier' => 'Pending: Courier',
                        'Completed: Courier' => 'Completed: Courier',
                        'Pending Admin: Self Pick-Up' => 'Pending Admin: Self Pick-Up',
                        'Pending Customer: Self Pick-Up' => 'Pending Customer: Self Pick-Up',
                        'Completed: Self Pick-Up' => 'Completed: Self Pick-Up',
                        'Pending: External Installation' => 'Pending: External Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Pending: Internal Installation' => 'Pending: Internal Installation',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::where('role_id', '4')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? $lead->lead_owner;
                    }),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        // ✅ Check for subsidiary company names from proforma invoices
                        $subsidiaryNames = [];

                        if (!empty($record->proforma_invoice_product)) {
                            $piProducts = is_array($record->proforma_invoice_product)
                                ? $record->proforma_invoice_product
                                : json_decode($record->proforma_invoice_product, true);

                            if (is_array($piProducts)) {
                                foreach ($piProducts as $piId) {
                                    $quotation = \App\Models\Quotation::find($piId);
                                    if ($quotation && $quotation->subsidiary_id) {
                                        $subsidiary = $quotation->subsidiary;
                                        if ($subsidiary && $subsidiary->company_name) {
                                            $subsidiaryNames[] = strtoupper(Str::limit($subsidiary->company_name, 25, '...'));
                                        }
                                    }
                                }
                            }
                        }

                        // Build the main company link
                        $html = '<div>';

                        // ✅ Add subsidiary names at the top with different styling
                        if (!empty($subsidiaryNames)) {
                            $uniqueSubsidiaryNames = array_unique($subsidiaryNames);
                            foreach ($uniqueSubsidiaryNames as $subsidiaryName) {
                                $html .= '<div style="font-size: 10px; color: #e67e22; font-weight: bold; margin-bottom: 3px; background: #fef9e7; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 4px;">
                                    ' . e($subsidiaryName) . '
                                </div><br>';
                            }
                        }

                        // Main company name
                        $html .= '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    style="color:#338cf0; text-decoration: none;">
                                    ' . $shortened . '
                                </a>';

                        $html .= '</div>';

                        return $html;
                    })
                    ->html(),

                TextColumn::make('installation_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'external_installation' => 'External Installation',
                        'internal_installation' => 'Internal Installation',
                        'self_pick_up' => 'Pick-Up',
                        'courier' => 'Courier',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Pending Stock' => new HtmlString('<span style="color: orange;">Pending Stock</span>'),
                        'Pending Migration' => new HtmlString('<span style="color: purple;">Pending Migration</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-all-table');
    }
}
