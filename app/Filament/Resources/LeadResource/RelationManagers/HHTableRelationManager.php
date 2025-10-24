<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Models\HardwareHandover;
use App\Models\User;
use Filament\Forms\Components\Builder;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class HHTableRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater2 = 0;

    #[On('refresh-hardware-handovers')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting logic that uses the raw ID value
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return url('admin/leads/' . $encryptedId);
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return strtoupper(Str::limit($record->lead->companyDetail->company_name, 30, '...'));
                        }

                        return $record->company_name ? strtoupper(Str::limit($record->company_name, 30, '...')) : '-';
                    })
                    ->color(function ($record) {
                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(),

                TextColumn::make('tc10_quantity')
                    ->label('TC10')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tc20_quantity')
                    ->label('TC20')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id5_quantity')
                    ->label('FACE ID 5')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id6_quantity')
                    ->label('FACE ID 6')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('time_beacon_quantity')
                    ->label('TIME BEACON')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nfc_tag_quantity')
                    ->label('NFC TAG')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_stock_at')
                    ->label(new HtmlString('Date<br>Pending Stock'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_migration_at')
                    ->label(new HtmlString('Date<br>Pending Migration'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label(new HtmlString('Date<br>Completed'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ]);
    }
}
