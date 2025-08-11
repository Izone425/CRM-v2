<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\HardwareHandover;
use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use App\Models\User;
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
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class ImplementerRequestCancelled extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
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

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getImplementerPendingRequests()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = \App\Models\ImplementerAppointment::query()
                ->where('request_status', 'Cancelled')
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->with(['lead', 'lead.companyDetail']);

        // Apply implementer filtering based on selected user
        if ($this->selectedUser === 'all-implementer') {
            // Show all implementer requests
        }
        elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer', $user->name);
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser->role_id === 4) {
                $query->where('implementer', $currentUser->name);
            }
        }

        return $query;
    }
    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getImplementerPendingRequests())
            ->defaultSort('date', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4, 5])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('Filter by Session Type')
                    ->options([
                        'IMPLEMENTATION SESSION' => 'Implementation Session',
                        'TRAINING SESSION' => 'Training Session',
                        'WEEKLY FOLLOW UP SESSION' => 'Weekly Follow Up Session',
                        'DATA MIGRATION' => 'Data Migration',
                        'LICENSE CERTIFICATION' => 'License Certification'
                    ])
                    ->placeholder('All Session Types')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Request ID')
                    ->formatStateUsing(function ($state) {
                        return 'IMP_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return url('admin/leads/' . $encryptedId);
                        }

                        return null;
                    })
                    ->getStateUsing(function (ImplementerAppointment $record): string {
                        // Fallback mechanism if relation is not available
                        if ($record->lead && $record->lead->companyDetail) {
                            return $record->lead->companyDetail->company_name ?? 'N/A';
                        } elseif ($record->company_name) {
                            return $record->company_name;
                        }
                        return 'N/A';
                    })
                    ->openUrlInNewTab()
                    ->color(function ($record) {
                        $company = CompanyDetail::where('company_name', $record->company_name)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),
                TextColumn::make('type')
                    ->label('Session Type')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('request_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'CANCELLED' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn() => auth()->user()->name === 'Fazuliana Mohdarsad')
                        ->action(function (\App\Models\ImplementerAppointment $record) {
                            $record->update(['request_status' => 'APPROVED']);

                            // Notification logic
                            Notification::make()
                                ->title('Request Approved')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-implementer-tables');
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn() => auth()->user()->name === 'Fazuliana Mohdarsad')
                        ->action(function (\App\Models\ImplementerAppointment $record) {
                            $record->update(['request_status' => 'REJECTED']);

                            // Notification logic
                            Notification::make()
                                ->title('Request Rejected')
                                ->warning()
                                ->send();

                            $this->dispatch('refresh-implementer-tables');
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-request-cancelled');
    }
}
