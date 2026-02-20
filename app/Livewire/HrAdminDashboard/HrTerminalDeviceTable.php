<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\HrTerminalDevice;

class HrTerminalDeviceTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HrTerminalDevice::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Enabled' => 'Enabled',
                        'Disabled' => 'Disabled',
                    ])
                    ->placeholder('All Status'),
            ])
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->company_name)
                    ->color('primary')
                    ->toggleable()
                    ->url(fn (HrTerminalDevice $record) => $record->handover_id
                        ? url("/admin/hr-company-license-details?" . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('serial_no')
                    ->label('Serial No')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('backend_device_id')
                    ->label('Backend Device Id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

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

                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->sortable()
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable(),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-terminal-device-table');
    }
}
