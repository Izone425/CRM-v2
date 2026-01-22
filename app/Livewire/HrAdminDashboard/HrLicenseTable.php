<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\HrLicense;
use App\Models\SoftwareHandover;

class HrLicenseTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
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

    #[On('refresh-license-table')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getLicenseCount()
    {
        return HrLicense::count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query(HrLicense::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'PAID' => 'Paid',
                        'TRIAL' => 'Trial',
                    ])
                    ->placeholder('All Types'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Enabled' => 'Enabled',
                        'Disabled' => 'Disabled',
                    ])
                    ->placeholder('All Status'),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From'),
                        DatePicker::make('end_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    })
                    ->label('Date Range'),
            ])
            ->columns([
                // Visible by default (3 columns)
                TextColumn::make('handover_id')
                    ->label('Handover ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->toggleable()
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HrLicense $record): View {
                                $softwareHandover = $record->softwareHandover;

                                if (!$softwareHandover) {
                                    // Try to find by parsing the handover_id
                                    $handoverId = $record->handover_id;
                                    if ($handoverId) {
                                        // Extract numeric ID from SW_YYXXXX format
                                        $numericId = (int) substr($handoverId, -4);
                                        $softwareHandover = SoftwareHandover::find($numericId);
                                    }
                                }

                                if (!$softwareHandover) {
                                    return view('components.no-handover-found');
                                }

                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $softwareHandover]);
                            })
                    ),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->company_name)
                    ->color('primary')
                    ->toggleable()
                    ->url(fn (HrLicense $record) => url("/admin/hr-company-license-details?" . http_build_query([
                        'handoverId' => $record->handover_id,
                        'softwareHandoverId' => $record->software_handover_id,
                    ]))),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Enabled' => 'success',
                        'Disabled' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                // Hidden by default (toggleable)
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAID' => 'success',
                        'TRIAL' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('auto_count_invoice_no')
                    ->label('AC Invoice No.')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_type')
                    ->label('License Type')
                    ->sortable()
                    ->searchable()
                    ->size('sm')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_limit')
                    ->label('Limit')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_user')
                    ->label('Users')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_login')
                    ->label('Logins')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('month')
                    ->label('Mth')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->sortable()
                    ->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->label('End')
                    ->sortable()
                    ->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('auto_renewal')
                    ->label('Auto-Renew')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Enabled' => 'info',
                        'Disabled' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(false)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(function (HrLicense $record): View {
                        $softwareHandover = $record->softwareHandover;

                        if (!$softwareHandover) {
                            // Try to find by parsing the handover_id
                            $handoverId = $record->handover_id;
                            if ($handoverId) {
                                // Extract numeric ID from SW_YYXXXX format
                                $numericId = (int) substr($handoverId, -4);
                                $softwareHandover = SoftwareHandover::find($numericId);
                            }
                        }

                        if (!$softwareHandover) {
                            return view('components.no-handover-found');
                        }

                        return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $softwareHandover]);
                    }),
            ])
            ->striped()
            ->defaultSort('handover_id', 'desc');
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-license-table');
    }
}
