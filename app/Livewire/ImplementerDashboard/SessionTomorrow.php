<?php

namespace App\Livewire\ImplementerDashboard;

use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Livewire\Attributes\On;

class SessionTomorrow extends Component implements HasForms, HasTable
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

    #[On('refresh-salesperson-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getAppointments()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();
        $tomorrow = Carbon::tomorrow(); // Change to tomorrow instead of today

        $query = ImplementerAppointment::whereDate('date', $tomorrow)
            ->where('status', 'New');

        if ($this->selectedUser === 'all-implementer') {

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

        return $query->orderBy('start_time', 'asc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getAppointments())
            ->columns([
                TextColumn::make('software_handover_id')
                    ->label('SW ID')
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state)) {
                            return 'N/A';
                        }

                        $yearDigits = '25'; // Default

                        // Try to get the software handover creation date
                        $softwareHandover = SoftwareHandover::where('id', $record->software_handover_id)
                            ->first();

                        if ($softwareHandover && $softwareHandover->created_at) {
                            $yearDigits = Carbon::parse($softwareHandover->created_at)->format('y');
                        }

                        if (Str::startsWith($state, 'SW_')) {
                            return $state;
                        }

                        $numericId = preg_replace('/[^0-9]/', '', $state);

                        return 'SW_' . $yearDigits . '0' . str_pad($numericId, 3, '0', STR_PAD_LEFT);
                    }),

                TextColumn::make('type')
                    ->label('SESSION TYPE'),

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

                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('session')
                    ->label('SESSION')
                    ->formatStateUsing(function ($state, $record) {
                        $startTime = Carbon::parse($record->start_time)->format('h:i A');
                        $endTime = Carbon::parse($record->end_time)->format('h:i A');
                        return "{$state}: {$startTime} - {$endTime}";
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'N/A')),
            ])
            ->filters([
                // Add any needed filters here
            ]);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.session-tomorrow');
    }
}
