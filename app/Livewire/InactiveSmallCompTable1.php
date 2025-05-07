<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class InactiveSmallCompTable1 extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getInactiveSmallCompanyLeads()
    {
        return Lead::query()
            ->where('categories', 'Inactive') // Only Inactive leads
            ->where('lead_status', '!=', 'Closed')
            ->where('done_call', '0')
            ->whereNull('salesperson')
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->selectRaw('*, DATEDIFF(updated_at, created_at) as pending_days');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getInactiveSmallCompanyLeads())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Inactive (1-24) - ' . $this->getInactiveSmallCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Filter for Lead Owner
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner'),
                SelectFilter::make('lead_status')
                    ->label('')
                    ->multiple()
                    ->options([
                        'Junk' => 'Junk',
                        'On Hold' => 'On Hold',
                        'Lost' => 'Lost',
                        'No Response' => 'No Response',
                    ])
                    ->placeholder('Select Lead Status'),
                Filter::make('created_month')
                    ->form([
                        TextInput::make('created_month')
                            ->type('month')
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['created_month'], function ($query, $date) {
                            // Extract month and year from the selected date
                            $month = Carbon::parse($date)->format('m');
                            $year = Carbon::parse($date)->format('Y');

                            // Filter records for the entire month
                            return $query->whereYear('created_at', $year)
                                        ->whereMonth('created_at', $month);
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_month'] ?? null) {
                            $indicators['created_month'] = 'Created in ' . Carbon::parse($data['created_month'])->format('F Y');
                        }

                        return $indicators;
                    })
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 10, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),
                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('j F Y, g:i A')),
                TextColumn::make('lead_status')
                    ->label('Lead Status')
                    ->sortable(),
                // TextColumn::make('pending_days')
                //     ->label('Pending Days')
                //     ->sortable()
                //     ->formatStateUsing(fn ($record) => $record->created_at->diffInDays($record->updated_at) . ' days')
                //     ->color(fn ($record) => $record->created_at->diffInDays($record->updated_at) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getInactiveTransferCallAttempt(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger')
            ]);
    }

    public function render()
    {
        return view('livewire.inactive-small-comp-table1');
    }
}
