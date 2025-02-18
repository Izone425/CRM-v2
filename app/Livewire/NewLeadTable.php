<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;

class NewLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser; // Allow dynamic filtering

    public function getPendingLeadsQuery()
    {
        return Lead::query()
            ->where('categories', 'New')
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days');
            // ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            // ->heading('New Leads')
            // ->heading(fn () => 'New Leads - ' . $this->getPendingLeadsQuery()->count() . ' Records') // Display count
            ->query($this->getPendingLeadsQuery()) // Use the new query method
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) =>
                        '<a href="' . url('admin/leads/' . \App\Classes\Encryptor::encrypt($record->id)) . '"
                            target="_blank"
                            class="inline-block"
                            style="color:#338cf0;">
                            ' . strtoupper(Str::limit($state ?? 'N/A', 10, '...')) . '
                        </a>'
                    )
                    ->html(),
                TextColumn::make('company_size_label')
                    ->label('Company Size')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN company_size = '1-24' THEN 1
                                WHEN company_size = '25-99' THEN 2
                                WHEN company_size = '100-500' THEN 3
                                WHEN company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    }),
                // TextColumn::make('created_at')
                //     ->label('Created Time')
                //     ->sortable()
                //     ->dateTime('d M Y, h:i A')
                //     ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                // TextColumn::make('details')->label('Details'),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->pending_days . ' days') // Use DB computed value
                    ->color(fn ($record) => $record->pending_days == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getViewAction(),
                    LeadActions::getAssignToMeAction(),
                    LeadActions::getAssignLeadAction(),
                ])
                ->button()
                ->color('warning'),
            ]);
    }

    public function render()
    {
        return view('livewire.new-lead-table');
    }
}
