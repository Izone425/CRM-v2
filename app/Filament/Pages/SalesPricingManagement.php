<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Pages/SalesPricingManagement.php
namespace App\Filament\Pages;

use App\Models\SalesPricing;
use App\Models\SalesPricingPage;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Livewire\WithPagination;

class SalesPricingManagement extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Sales Pricing Management';
    protected static ?string $title = 'Sales Pricing';
    protected static ?int $navigationSort = 29;
    protected static ?string $navigationGroup = 'Sales Management';
    protected static string $view = 'filament.pages.sales-pricing-management';

    public $search = '';
    public $selectedPricing = null;
    public $selectedPage = null;
    public $currentPageIndex = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_new')
                ->label('Add New')
                ->url(route('filament.admin.resources.sales-pricings.create'))
                ->button()
                ->visible(fn(): bool => auth()->check() && in_array(auth()->user()->role_id, [2, 3])), // Salesperson and Manager
        ];
    }

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectPricing($pricingId): void
    {
        $this->selectedPricing = SalesPricing::with('pages')->find($pricingId);
        $this->currentPageIndex = 0;

        if ($this->selectedPricing && $this->selectedPricing->pages->count() > 0) {
            $this->selectedPage = $this->selectedPricing->pages->first();
        } else {
            $this->selectedPage = null;
        }
    }

    public function goToPage($index): void
    {
        if ($this->selectedPricing && isset($this->selectedPricing->pages[$index])) {
            $this->currentPageIndex = $index;
            $this->selectedPage = $this->selectedPricing->pages[$index];
        }
    }

    public function nextPage(): void
    {
        if ($this->selectedPricing &&
            $this->currentPageIndex < $this->selectedPricing->pages->count() - 1) {
            $this->currentPageIndex++;
            $this->selectedPage = $this->selectedPricing->pages[$this->currentPageIndex];
        }
    }

    public function prevPage(): void
    {
        if ($this->selectedPricing && $this->currentPageIndex > 0) {
            $this->currentPageIndex--;
            $this->selectedPage = $this->selectedPricing->pages[$this->currentPageIndex];
        }
    }

    public function getSalesPricings()
    {
        $userRoleId = auth()->user()->role_id;

        $query = SalesPricing::query()
            ->with(['pages'])
            ->where('status', 'Active')
            ->when($this->search !== '', function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            });

        // Filter by access rights
        $query->where(function ($q) use ($userRoleId) {
            $q->whereNull('access_right')
              ->orWhere('access_right', '[]')
              ->orWhereJsonContains('access_right', (string)$userRoleId);
        });

        return $query->orderBy('title')->get();
    }

    protected function getViewData(): array
    {
        return [
            'pricings' => $this->getSalesPricings(),
        ];
    }
}
