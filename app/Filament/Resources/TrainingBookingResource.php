<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingBookingResource\Pages;
use App\Models\TrainingBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
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
                    ])
                    ->columnSpan(['lg' => 1]),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (TrainingBooking $record): ?string => $record->created_at ? $record->created_at->diffForHumans() : null),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Updated at')
                            ->content(fn (TrainingBooking $record): ?string => $record->updated_at ? $record->updated_at->diffForHumans() : null),
                    ])
                    ->columnSpan(['lg' => 1]),

                Forms\Components\Section::make('Attendees')
                    ->schema([
                        Forms\Components\Repeater::make('attendees')
                            ->relationship()
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
                Tables\Columns\TextColumn::make('training_date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pax_count')
                    ->label('Attendees')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'confirmed',
                        'danger' => 'cancelled',
                        'success' => 'completed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
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
            'create' => Pages\CreateTrainingBooking::route('/create'),
            'edit' => Pages\EditTrainingBooking::route('/{record}/edit'),
        ];
    }
}
