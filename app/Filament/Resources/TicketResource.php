<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product')
                    ->label('Product')
                    ->required()
                    ->options([
                        'TimeTec HR' => 'TimeTec HR',
                        'TimeTec TA' => 'TimeTec TA',
                        'TimeTec Leave' => 'TimeTec Leave',
                    ]),

                Forms\Components\Select::make('module')
                    ->label('Module')
                    ->options([
                        'TimeTec HR' => 'TimeTec HR',
                        'TimeTec TA' => 'TimeTec TA',
                        'TimeTec Leave' => 'TimeTec Leave',
                    ])
                    ->required(),

                Forms\Components\Select::make('device_type')
                    ->label('Device Type')
                    ->options([
                        'Web' => 'Web',
                        'Mobile' => 'Mobile',
                        'Hardware' => 'Hardware',
                    ]),

                Forms\Components\Select::make('priority')
                    ->label('Priority')
                    ->required()
                    ->options([
                        'Low' => 'Low',
                        'Medium' => 'Medium',
                        'High' => 'High',
                        'Critical' => 'Critical',
                    ]),

                Forms\Components\Select::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_expiring_license')
                            ->select('f_company_name', 'f_created_time')
                            ->groupBy('f_company_name', 'f_created_time')
                            ->orderBy('f_created_time', 'desc')
                            ->get()
                            ->mapWithKeys(function ($company) {
                                return [$company->f_company_name => strtoupper($company->f_company_name)];
                            })
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_expiring_license')
                            ->select('f_company_name', 'f_created_time')
                            ->where('f_company_name', 'like', "%{$search}%")
                            ->groupBy('f_company_name', 'f_created_time')
                            ->orderBy('f_created_time', 'desc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($company) {
                                return [$company->f_company_name => strtoupper($company->f_company_name)];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        return strtoupper($value);
                    }),

                Forms\Components\TextInput::make('zoho_ticket_number')
                    ->label('Zoho Ticket Number'),

                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\RichEditor::make('description')
                    ->label('Description'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Open' => 'Open',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ])
                    ->default('Open'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('product')->sortable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'success' => 'Low',
                        'warning' => 'Medium',
                        'danger' => 'High',
                        'danger' => 'Critical',
                    ]),
                Tables\Columns\BadgeColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    // Hook to post to API after creating
    public static function afterCreate($record): void
    {
        try {
            Http::post(url('/api/tickets'), $record->toArray());
        } catch (\Exception $e) {
            Log::error('Failed to post ticket to API: ' . $e->getMessage());
        }
    }
}
