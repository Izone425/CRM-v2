<?php

namespace App\Livewire;

use App\Models\SoftwareHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class SoftwareHandoverV2Draft extends Component implements HasForms, HasTable
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

    #[On('refresh-softwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewSoftwareHandovers())
            ->emptyState(fn() => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('id')
                    ->label('SW ID')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->formatted_handover_id;
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('lead.name')
                    ->label('Lead Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('training_type')
                    ->label('Training Type')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->striped();
    }

    public function getNewSoftwareHandovers(): Builder
    {
        return SoftwareHandover::query()
            ->where('status', 'Draft')
            ->where('hr_version', 2)
            ->with(['lead']);
    }

    public function render(): View
    {
        return view('livewire.software-handover-v2-draft');
    }
}
