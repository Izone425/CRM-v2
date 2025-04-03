<?php
namespace App\Livewire;

use App\Models\Lead;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Livewire\Attributes\On;
use Carbon\Carbon;

class SalesForecastTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $selectedMonth;
    public $totals = [
        'hot' => 0,
        'warm' => 0,
        'cold' => 0,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser, $selectedMonth)
    {
        $this->selectedUser = $selectedUser === "" ? null : $selectedUser;
        $this->selectedMonth = $selectedMonth === "" ? null : $selectedMonth;

        session(['selectedUser' => $this->selectedUser]);
        session(['selectedMonth' => $this->selectedMonth]);

        $this->calculateTotals(); // Update totals dynamically
        $this->resetTable(); // Refresh the table
    }

    public function getFilteredLeadsQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
        $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

        $query = Lead::query()
            ->whereIn('lead_status', ['Hot', 'Warm', 'Cold']);

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        return $query
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days')
            ->orderByRaw("FIELD(lead_status, 'Hot', 'Warm', 'Cold')")
            ->orderByDesc('created_at');
    }

    /**
     * Calculate total deal_amount for Hot, Warm, and Cold leads.
     */
    public function calculateTotals()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
        $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

        $query = Lead::query()->whereIn('lead_status', ['Hot', 'Warm', 'Cold']);

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        $totals = $query->selectRaw("
            SUM(CASE WHEN lead_status = 'Hot' THEN deal_amount ELSE 0 END) as hot,
            SUM(CASE WHEN lead_status = 'Warm' THEN deal_amount ELSE 0 END) as warm,
            SUM(CASE WHEN lead_status = 'Cold' THEN deal_amount ELSE 0 END) as cold
        ")->first();

        $this->totals = [
            'hot' => $totals->hot ?? 0,
            'warm' => $totals->warm ?? 0,
            'cold' => $totals->cold ?? 0,
        ];
    }

    public function mount()
    {
        $this->calculateTotals();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getFilteredLeadsQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([10, 25, 50, 100, 'all'])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable(),
                TextColumn::make('lead_status')
                    ->label('Lead Status')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Hot' => 'danger',
                        'Warm' => 'warning',
                        'Cold' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime('d M Y'),
                TextColumn::make('deal_amount')
                    ->label('Deal Amount')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-'),
            ]);
    }
}
