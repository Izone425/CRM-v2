<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Actions\ImplementerActions;
use App\Models\ImplementerAppointment;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;

class ImplementerSessionPending extends Component implements HasForms, HasTable
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

    public function getAppointments()
    {
        $currentUser = auth()->user();

        // Get current date for comparison
        $today = Carbon::now()->format('Y-m-d');

        $query = ImplementerAppointment::query()
            ->where('status', 'New') // Only pending/new appointments
            ->where('date', '>=', $today) // Future or today's appointments
            ->whereNotNull('lead_id'); // Only appointments with lead_id

        // Filter by implementer if user is implementer role
        if (in_array($currentUser->role_id, [4, 5])) {
            $query->where('implementer', $currentUser->name);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getAppointments())
            ->columns([
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('COMPANY NAME')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }
                        return 'N/A';
                    })
                    ->url(function ($record) {
                        if ($record->lead_id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);
                            return url('admin/leads/' . $encryptedId);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->color(Color::hex('#338cf0')),

                TextColumn::make('type')
                    ->label('Session Type')
                    ->formatStateUsing(function (string $state): string {
                        $words = str_word_count($state);
                        if ($words > 3) {
                            return str_replace(' SESSION', '<br>SESSION', $state);
                        }
                        return $state;
                    })
                    ->html()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_time)->format('g:i A');
                        $end = Carbon::parse($record->end_time)->format('g:i A');
                        return "{$start} - {$end}";
                    })
                    ->sortable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    ImplementerActions::viewAppointmentAction(),

                    ImplementerActions::cancelAppointmentAction(),

                    ImplementerActions::sendSessionSummaryAction(),
                ])
                ->icon('heroicon-m-list-bullet')
                ->size(\Filament\Support\Enums\ActionSize::Small)
                ->color('primary')
                ->button(),
            ])
            ->defaultSort('date', 'asc')
            ->paginated([10, 25]);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-session-pending');
    }
}
