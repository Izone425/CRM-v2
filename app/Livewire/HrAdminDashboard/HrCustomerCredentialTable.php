<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Component;
use App\Models\Customer;
use App\Models\SoftwareHandover;

class HrCustomerCredentialTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->whereNotNull('sw_id')
                    ->with(['softwareHandover'])
            )
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Sales Person')
                    ->options(function () {
                        return SoftwareHandover::whereNotNull('salesperson')
                            ->distinct()
                            ->pluck('salesperson', 'salesperson')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $swIds = SoftwareHandover::where('salesperson', $data['value'])
                                ->pluck('id')
                                ->toArray();
                            $query->whereIn('sw_id', $swIds);
                        }
                    })
                    ->placeholder('All Sales Person'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'valid' => 'Valid',
                        'active' => 'Active',
                        'invalid' => 'Invalid',
                    ])
                    ->placeholder('All Status'),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time Creation')
                    ->sortable()
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable(),

                TextColumn::make('softwareHandover.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->softwareHandover?->company_name)
                    ->toggleable(),

                TextColumn::make('softwareHandover.salesperson')
                    ->label('Sales Person')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Master Email')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('plain_password')
                    ->label('Password')
                    ->getStateUsing(fn ($record) => $record->getAttributes()['plain_password'] ?? 'N/A')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                        'valid', 'active' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-customer-credential-table');
    }
}
