<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Livewire\Attributes\Validate;
use Illuminate\Support\Carbon;
use Filament\Actions\Action;

class SearchLead extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Search Lead';
    protected static ?string $title = 'Search Lead';
    protected static string $view = 'filament.pages.search-lead';

    // Search properties
    #[Validate('nullable|string|max:255')]
    public string $companySearchTerm = '';

    #[Validate('nullable|string|max:255')]
    public string $phoneSearchTerm = '';

    public bool $hasSearched = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal properties for time since creation
    public $showTimeSinceModal = false;
    public $selectedLead = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.search-lead');
    }

    public function mount(): void
    {
        $this->hasSearched = false;
    }

    public function updatingCompanySearchTerm(): void
    {
        $this->hasSearched = false;
    }

    public function updatingPhoneSearchTerm(): void
    {
        $this->hasSearched = false;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    // Add this method to open the time since creation modal
    public function openTimeSinceModal($leadId)
    {
        $this->selectedLead = Lead::find($leadId);
        $this->showTimeSinceModal = true;
    }

    public function closeTimeSinceModal()
    {
        $this->showTimeSinceModal = false;
        $this->selectedLead = null;
    }

    public function searchCompany(): void
    {
        try {
            // Validate that there's something to search for
            if (empty($this->companySearchTerm) && empty($this->phoneSearchTerm)) {
                Notification::make()
                    ->title('Search Error')
                    ->body('Please enter a search term - either a company name or phone number to search.')
                    ->danger()
                    ->send();
                return;
            }

            // Set searched flag
            $this->hasSearched = true;

            Notification::make()
                ->title('Search Complete')
                ->body('Found ' . $this->getLeads()->count() . ' results.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Search Error')
                ->body('An error occurred while searching. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function resetSearch(): void
    {
        try {
            $this->companySearchTerm = '';
            $this->phoneSearchTerm = '';
            $this->hasSearched = false;
            $this->resetErrorBag();

            Notification::make()
                ->title('Search Cleared')
                ->body('Search has been reset.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Reset Error')
                ->body('An error occurred while resetting search.')
                ->danger()
                ->send();
        }
    }

    public function getLeads()
    {
        if (!$this->hasSearched || (empty($this->companySearchTerm) && empty($this->phoneSearchTerm))) {
            return collect(); // Return empty collection
        }

        $query = Lead::query()->with(['companyDetail']);

        // Build search conditions
        $query->where(function (Builder $searchQuery) {
            // Company name search
            if (!empty($this->companySearchTerm)) {
                $searchQuery->whereHas('companyDetail', function (Builder $subQuery) {
                    $subQuery->where('company_name', 'like', "%{$this->companySearchTerm}%");
                });
            }

            // Phone number search (OR condition if both terms exist)
            if (!empty($this->phoneSearchTerm)) {
                if (!empty($this->companySearchTerm)) {
                    // If both searches exist, use OR
                    $searchQuery->orWhere(function (Builder $phoneQuery) {
                        $phoneQuery->where('phone', 'like', "%{$this->phoneSearchTerm}%")
                                 ->orWhereHas('companyDetail', function (Builder $contactQuery) {
                                     $contactQuery->where('contact_no', 'like', "%{$this->phoneSearchTerm}%");
                                 });
                    });
                } else {
                    // If only phone search exists
                    $searchQuery->where('phone', 'like', "%{$this->phoneSearchTerm}%")
                              ->orWhereHas('companyDetail', function (Builder $contactQuery) {
                                  $contactQuery->where('contact_no', 'like', "%{$this->phoneSearchTerm}%");
                              });
                }
            }
        });

        // Return all results without pagination
        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    // Helper methods to match the table structure
    public function getLeadOwner($lead)
    {
        return $lead->lead_owner ?? '-';
    }

    public function getSalesperson($lead)
    {
        return User::find($lead->salesperson)?->name ?? '-';
    }

    public function getLeadStatus($lead)
    {
        return $lead->lead_status;
    }

    public function getLeadStatusColor($lead)
    {
        $status = $lead->lead_status;
        $leadStatusEnum = LeadStatusEnum::tryFrom($status);
        return $leadStatusEnum ? $leadStatusEnum->getColor() : '#6b7280';
    }

    public function getCompanyName($lead)
    {
        return $lead->companyDetail?->company_name ?? '-';
    }

    // Add method to get time since creation data for the modal
    public function getTimeSinceCreationData($lead)
    {
        $createdAt = $lead->created_at;
        $now = now();

        $diffInDays = $createdAt->diffInDays($now);
        $diffInHours = $createdAt->copy()->addDays($diffInDays)->diffInHours($now);
        $diffInMinutes = $createdAt->copy()->addDays($diffInDays)->addHours($diffInHours)->diffInMinutes($now);

        $humanReadable = $createdAt->diffForHumans();

        // Format detailed time breakdown
        $detailedBreakdown = '';
        if ($diffInDays > 0) {
            $detailedBreakdown .= "{$diffInDays} day" . ($diffInDays > 1 ? 's' : '') . ", ";
        }
        if ($diffInHours > 0 || $diffInDays > 0) {
            $detailedBreakdown .= "{$diffInHours} hour" . ($diffInHours > 1 ? 's' : '') . ", ";
        }
        $detailedBreakdown .= "{$diffInMinutes} minute" . ($diffInMinutes > 1 ? 's' : '');

        return [
            'record' => $lead,
            'created_at' => $createdAt->format('d M Y, h:i A'),
            'human_readable' => $humanReadable,
            'detailed_breakdown' => $detailedBreakdown,
            'diff_in_days' => $diffInDays,
            'diff_in_hours' => $diffInHours,
            'diff_in_minutes' => $diffInMinutes,
        ];
    }
}
