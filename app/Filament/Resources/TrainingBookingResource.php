<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingBookingResource\Pages;
use App\Models\TrainingBooking;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class TrainingBookingResource extends Resource
{
    protected static ?string $model = TrainingBooking::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Training Bookings';
    protected static ?string $navigationGroup = 'Training Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('company_id')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        // Get company details directly instead of going through leads
                                        $query = \App\Models\CompanyDetail::query()
                                            ->when(auth()->user()->role_id == 2, function ($q) {
                                                // Join with leads to filter by salesperson
                                                $q->whereHas('lead', function ($leadQuery) {
                                                    $leadQuery->where('salesperson', auth()->id());
                                                });
                                            })
                                            ->get();

                                        return $query
                                            ->filter(fn($company) => $company->company_name) // Filter out companies with no name
                                            ->mapWithKeys(function ($company) {
                                                return [$company->id => $company->company_name];
                                            })
                                            ->toArray();
                                    })
                                    ->disabled()
                                    ->default(function ($livewire) {
                                        // When creating from request query parameter
                                        if (request()->has('company_id')) {
                                            $companyId = request()->query('company_id');
                                            $company = \App\Models\CompanyDetail::find($companyId);

                                            // Return the company ID if we found a valid company with a name
                                            if ($company && $company->company_name) {
                                                return $company->id;
                                            }
                                            return null;
                                        }

                                        // When editing an existing record
                                        if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->record) {
                                            return $livewire->record->company_id; // Simply return the stored company_id
                                        }

                                        return null;
                                    })
                                    ->label('Company'),

                                Forms\Components\DatePicker::make('training_date')
                                    ->required()
                                    ->label('Training Date'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])
                                    ->default('confirmed')
                                    ->required(),

                                Forms\Components\TextInput::make('pax_count')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->label('Number of Attendees'),

                                Forms\Components\Textarea::make('additional_notes')
                                    ->label('Additional Notes')
                                    ->maxLength(1000)
                                    ->columnSpan('full'),
                            ]),
                    ]),

                Forms\Components\Section::make('Attendees')
                    ->schema([
                        Forms\Components\Repeater::make('attendees')
                            ->relationship()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(50),

                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'confirmed' => 'Confirmed',
                                                'checked_in' => 'Checked In',
                                                'no_show' => 'No Show',
                                            ])
                                            ->default('confirmed')
                                            ->required(),
                                    ]),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->columnSpan('full'),
                    ])
                    ->columnSpan('full'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),

                TextColumn::make('company_id')
                    ->label('Company')
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            $companyDetail = \App\Models\CompanyDetail::find($state);
                            return $companyDetail?->company_name ?? 'Unknown Company';
                        }
                        return 'No Company';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->whereHas('company', function (Builder $q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('training_date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('pax_count')
                    ->label('Attendees')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'confirmed',
                        'danger' => 'cancelled',
                        'success' => 'completed',
                    ]),

                TextColumn::make('created_by')
                    ->label('Created By')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return User::find($state)?->name ?? '-';
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),

                Tables\Filters\Filter::make('training_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('training_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('training_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('copyAttendees')
                ->label('Copy Attendees')
                ->icon('heroicon-o-users')
                ->modalHeading('Copy Attendees From Another Booking')
                ->modalDescription('Select a training booking to copy attendees from')
                ->modalSubmitActionLabel('Copy Attendees')
                ->form([
                    Forms\Components\Select::make('source_booking_id')
                        ->label('Source Booking')
                        ->options(function (TrainingBooking $record) {
                            return TrainingBooking::where('id', '!=', $record->id)
                                ->when(auth()->user()->role_id == 2, function ($q) {
                                    // If salesperson, only show their bookings
                                    $q->whereHas('companyDetail.lead', function ($leadQuery) {
                                        $leadQuery->where('salesperson', auth()->id());
                                    });
                                })
                                ->get()
                                ->mapWithKeys(function ($booking) {
                                    // Get company name from the CompanyDetail model using the company_id
                                    $companyName = 'Unknown Company';

                                    if ($booking->company_id) {
                                        // Try to find the company details
                                        $companyDetail = \App\Models\CompanyDetail::find($booking->company_id);
                                        if ($companyDetail && $companyDetail->company_name) {
                                            $companyName = $companyDetail->company_name;
                                        }
                                    }

                                    // Fix the date formatting issue by handling both string and Carbon instances
                                    $date = 'No date';
                                    if ($booking->training_date) {
                                        $date = $booking->training_date instanceof \Carbon\Carbon
                                            ? $booking->training_date->format('j M Y')
                                            : Carbon::parse($booking->training_date)->format('j M Y');
                                    }

                                    return [$booking->id => "{$companyName} - {$date} ({$booking->pax_count} attendees)"];
                                });
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (TrainingBooking $record, array $data): void {
                    $sourceBooking = TrainingBooking::findOrFail($data['source_booking_id']);

                    // Copy attendees
                    foreach ($sourceBooking->attendees as $attendee) {
                        $record->attendees()->create([
                            'name' => $attendee->name,
                            'email' => $attendee->email,
                            'phone' => $attendee->phone,
                            'status' => 'confirmed', // Default to confirmed for new copies
                        ]);
                    }

                    // Update the pax count if necessary
                    if ($record->pax_count < $record->attendees()->count()) {
                        $record->update([
                            'pax_count' => $record->attendees()->count()
                        ]);
                    }

                    // Show success notification
                    Notification::make()
                        ->title('Attendees Copied Successfully')
                        ->success()
                        ->send();
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'confirmed' => 'Confirmed',
                                    'cancelled' => 'Cancelled',
                                    'completed' => 'Completed',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
       return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingBookings::route('/'),
            // 'create' => Pages\CreateTrainingBooking::route('/create'),
            'edit' => Pages\EditTrainingBooking::route('/{record}/edit'),
        ];
    }
}
