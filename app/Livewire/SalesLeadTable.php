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

class SalesLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    // Track if a search has been performed
    public $isSearched = false;
    public $searchCompanyName = '';

    // Base query that will return no results by default
    public function getTableQuery(): Builder
    {
        $query = Lead::query();

        // Only return results if a search has been performed
        if (!$this->isSearched) {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        // Apply company name filter if provided
        if (!empty($this->searchCompanyName)) {
            $query->whereHas('companyDetail', function ($subquery) {
                $subquery->where('company_name', 'like', '%' . $this->searchCompanyName . '%');
            });
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->filters([
                Filter::make('company_name')
                    ->form([
                        TextInput::make('company_name')
                            ->hiddenLabel()
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Set the search status flags when filter is applied
                        $this->isSearched = true;
                        $this->searchCompanyName = $data['company_name'] ?? '';

                        // The actual filtering is now handled in getTableQuery()
                        // This prevents duplicate filtering logic
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['company_name']) && !empty($data['company_name'])
                            ? 'Company Name: ' . $data['company_name']
                            : null;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                // Rest of your columns remain the same
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
            ]);
    }

    public function render()
    {
        return view('livewire.sales-lead-table');
    }
}
