<?php
namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use Livewire\Component;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class TicketListZoho extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $listeners = ['ticket-status-updated' => '$refresh'];

    public function render()
    {
        return view('livewire.ticket-list-zoho');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::whereIn('product_id', [1, 2])
                    ->whereNotNull('zoho_id')
                    ->where('zoho_id', '!=', '')
                    ->where('zoho_id', '!=', 'N/A')
            )
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('zoho_id')
                    ->label('Zoho Ticket')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('warning')
                    ->copyable()
                    ->copyMessage('Zoho ID copied'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Ticket $record): string => $record->title ?? ''),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper(substr($state ?? '', 0, 20))),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->default('N/A')
                    ->formatStateUsing(fn ($state) =>
                        $state === 'TimeTec HR - Version 1' ? 'V1' :
                        ($state === 'TimeTec HR - Version 2' ? 'V2' : $state)
                    ),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('priority.name')
                    ->label('Priority')
                    ->colors([
                        'danger' => fn ($state) => str_contains(strtolower($state ?? ''), 'bug') || str_contains(strtolower($state ?? ''), 'software'),
                        'warning' => fn ($state) => str_contains(strtolower($state ?? ''), 'backend') || str_contains(strtolower($state ?? ''), 'assistance'),
                        'primary' => fn ($state) => str_contains(strtolower($state ?? ''), 'critical enhancement'),
                        'info' => fn ($state) => str_contains(strtolower($state ?? ''), 'paid') || str_contains(strtolower($state ?? ''), 'customization'),
                        'success' => fn ($state) => str_contains(strtolower($state ?? ''), 'non-critical'),
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'New',
                        'warning' => 'In Progress',
                        'success' => 'Completed',
                        'danger' => 'Closed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->options([
                        1 => 'Version 1',
                        2 => 'Version 2',
                    ]),

                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(
                        TicketModule::where('is_active', true)
                            ->whereIn('name', [
                                'PROFILE',
                                'ATTENDANCE',
                                'LEAVE',
                                'CLAIM',
                                'PAYROLL',
                                'APPRAISAL',
                                'HIRE',
                                'IOT'
                            ])
                            ->orderByRaw("FIELD(name, 'PROFILE', 'ATTENDANCE', 'LEAVE', 'CLAIM', 'PAYROLL', 'APPRAISAL', 'HIRE', 'IOT')")
                            ->pluck('name', 'id')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->options(
                        TicketPriority::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}
