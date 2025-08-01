<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Filament\Actions\LeadActions;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class SearchLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    // Add custom search property
    public $companySearchTerm = '';
    public $hasSearched = false;

    // Base query that will return no results by default
    public function getTableQuery(): Builder
    {
        $query = Lead::query();

        // Always start with an empty result set unless we have valid search criteria
        if (!$this->hasSearched || empty($this->companySearchTerm)) {
            // This returns an impossible condition to ensure no records are returned
            $query->whereRaw('1 = 0');
        } else {
            // Only search if hasSearched flag is true AND we have a search term
            $query->whereHas('companyDetail', function (Builder $subQuery) {
                $subQuery->where('company_name', 'like', "%{$this->companySearchTerm}%");
            });
        }

        return $query;
    }

    // Custom search method that will be triggered by the button
    public function searchCompany()
    {
        // First validate that there's something to search for
        if (empty($this->companySearchTerm)) {
            // Optional: show a notification if search term is empty
            Notification::make()
                ->warning()
                ->title('Please enter a search term')
                ->send();
            return;
        }

        // Set searched flag and reset pagination in one operation
        $this->hasSearched = true;

        // This is critical - force Livewire to re-render the component
        // Which will trigger a new query
        $this->resetPage();
    }

    // Reset search
    public function resetSearch()
    {
        $this->companySearchTerm = '';
        $this->hasSearched = false;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->poll('1s')
            ->defaultSort('created_at', 'desc')
            ->emptyState(function () {
                if ($this->hasSearched) {
                    return view('components.empty-state-no-results', ['searchTerm' => $this->companySearchTerm]);
                }
                return view('components.empty-state-question');
            })
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('lead_owner')
                    ->label('LEAD OWNER')
                    ->getStateUsing(fn (Lead $record) => $record->lead_owner ?? '-'),
                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(fn (Lead $record) => \App\Models\User::find($record->salesperson)?->name ?? '-'),
                TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->dateTime('d M Y, h:i A')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('lead_status')
                    ->label('LEAD STATUS')
                    ->alignCenter()
                    ->extraAttributes(fn($state) => [
                        'style' => optional(LeadStatusEnum::tryFrom($state))->getColor()
                            ? "background-color: " . LeadStatusEnum::tryFrom($state)->getColor() . ";" .
                              "border-radius: 25px; width: 90%; height: 27px;" .
                              (in_array($state, ['Hot', 'Warm', 'Cold', 'RFQ-Transfer']) ? "color: white;" : "")
                            : '',
                    ]),
                TextColumn::make('company_name')
                    ->wrap()
                    ->label('COMPANY NAME')
                    ->weight(FontWeight::Bold)
                    ->getStateUsing(fn (Lead $record) => $record->companyDetail?->company_name ?? '-'),
                TextColumn::make('company_size_label')
                    ->label('COMPANY SIZE'),
                TextColumn::make('company_size')
                    ->label('HEADCOUNT'),
            ])->actions([
                ActionGroup::make([
                    LeadActions::getTimeSinceCreationAction(),
                ])
                ->button(),
            ]);
    }

    public function render()
    {
        return view('livewire.search-lead-table');
    }
}
