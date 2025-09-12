<?php
namespace App\Filament\Pages;

use App\Models\Policy;
use App\Models\PolicyPage;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Livewire\WithPagination;

class PolicyManagement extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Policy Management';
    protected static ?string $title = 'Policies';
    protected static ?int $navigationSort = 25;
    protected static ?string $navigationGroup = 'Administration';
    protected static string $view = 'filament.pages.policy-management';

    public $search = '';
    public $selectedPolicy = null;
    public $selectedPage = null;
    public $currentPageIndex = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_new')
                ->label('Add New')
                ->url(route('filament.admin.resources.policies.create'))
                ->button()
                ->visible(fn(): bool => auth()->check() && auth()->user()->role_id === 3),
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

    public function selectPolicy($policyId): void
    {
        $this->selectedPolicy = Policy::with('pages')->find($policyId);
        $this->currentPageIndex = 0;

        if ($this->selectedPolicy && $this->selectedPolicy->pages->count() > 0) {
            $this->selectedPage = $this->selectedPolicy->pages->first();
        } else {
            $this->selectedPage = null;
        }
    }

    public function goToPage($index): void
    {
        if ($this->selectedPolicy && isset($this->selectedPolicy->pages[$index])) {
            $this->currentPageIndex = $index;
            $this->selectedPage = $this->selectedPolicy->pages[$index];
        }
    }

    public function nextPage(): void
    {
        if ($this->selectedPolicy &&
            $this->currentPageIndex < $this->selectedPolicy->pages->count() - 1) {
            $this->currentPageIndex++;
            $this->selectedPage = $this->selectedPolicy->pages[$this->currentPageIndex];
        }
    }

    public function prevPage(): void
    {
        if ($this->selectedPolicy && $this->currentPageIndex > 0) {
            $this->currentPageIndex--;
            $this->selectedPage = $this->selectedPolicy->pages[$this->currentPageIndex];
        }
    }

    public function getPolicies()
    {
        $userRoleId = auth()->user()->role_id;
        $userRoleIdJson = json_encode((string)$userRoleId); // Convert to JSON string format

        $query = Policy::query()
            ->with(['pages', 'category'])
            ->where('status', 'Active') // Only show Active policies
            ->when($this->search !== '', function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            });

        // Filter policies by category access rights
        $query->whereHas('category', function ($categoryQuery) use ($userRoleId, $userRoleIdJson) {
            $categoryQuery->where(function ($q) use ($userRoleId, $userRoleIdJson) {
                $q->whereNull('access_right')  // Categories with no restrictions
                ->orWhere('access_right', '[]') // Empty JSON array
                ->orWhereRaw("JSON_CONTAINS(access_right, ?)", [$userRoleIdJson]); // Contains user's role
            });
        });

        return $query->orderBy('title')->get();
    }

    protected function getViewData(): array
    {
        return [
            'policies' => $this->getPolicies(),
        ];
    }
}
