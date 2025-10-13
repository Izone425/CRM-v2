<?php

namespace App\Livewire\AdminHeadcountDashboard;

use App\Models\HeadcountHandover;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HeadcountCompletedTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getCompletedHeadcountHandovers()
    {
        return HeadcountHandover::with(['lead.companyDetail', 'creator', 'completedBy'])
            ->where('status', 'Completed')
            ->orderBy('completed_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getCompletedHeadcountHandovers())
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('completed_at')
                    ->label('Date Completed')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('creator.name')
                    ->label('Salesperson')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Completed' => new HtmlString('<span style="color: green; font-weight: bold;">Completed</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.admin-headcount-dashboard.headcount-completed-table');
    }
}
