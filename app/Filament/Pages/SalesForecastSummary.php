<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use Livewire\Attributes\On;

class SalesForecastSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.sales-forecast-summary';
    protected static ?string $navigationLabel = 'Sales Forecast Summary';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 10;

    public $selectedYear;
    public $selectedMonth;

    public function mount()
    {
        session(['selectedYear' => $this->selectedYear]);
        session(['selectedMonth' => $this->selectedMonth]);
    }

    public function updatedSelectedYear($year)
    {
        $this->selectedYear = $year;
        session(['selectedYear' => $year]);
        $this->dispatch('updateSalesForecastTable', $year, $this->selectedMonth);
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);
        $this->dispatch('updateSalesForecastTable', $this->selectedYear, $month);
    }
}
