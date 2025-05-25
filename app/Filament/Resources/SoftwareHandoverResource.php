<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareHandoverResource\Pages;
use App\Filament\Resources\SoftwareHandoverResource\RelationManagers;
use App\Models\SoftwareHandover;
use App\Services\CategoryService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Illuminate\Support\Str;

class SoftwareHandoverResource extends Resource
{
    protected static ?string $model = SoftwareHandover::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Section: Company Details
            Section::make('Company Information')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->readonly()
                                ->maxLength(255),

                            TextInput::make('salesperson')
                                ->label('Salesperson')
                                ->placeholder('Select salesperson')
                                ->readonly(),

                            TextInput::make('pic_name')
                                ->label('PIC Name')
                                ->readonly()
                                ->maxLength(255),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('pic_phone')
                                ->label('PIC Phone')
                                ->readonly()
                                ->maxLength(20),

                            TextInput::make('headcount')
                                ->numeric()
                                ->readonly(),

                            TextInput::make('category')
                                ->label('Company Size')
                                ->formatStateUsing(function ($state, $record) {
                                    // If the record has headcount, derive category from it
                                    if ($record && isset($record->headcount)) {
                                        $categoryService = app(CategoryService::class);
                                        return $categoryService->retrieve($record->headcount);
                                    }

                                    // Otherwise, return the stored category value
                                    return $state;
                                })
                                ->dehydrated(false)
                                ->readonly()
                        ]),
                ]),

            Grid::make(6)
            ->schema([
                // Section: Modules
                Section::make('Module Selection')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)
                        ->schema([
                            Checkbox::make('ta')
                                ->label('Time Attendance (TA)')
                                ->inline(),

                            Checkbox::make('tl')
                                ->label('TimeTec Leave (TL)')
                                ->inline(),

                            Checkbox::make('tc')
                                ->label('TimeTec Claim (TC)')
                                ->inline(),

                            Checkbox::make('tp')
                                ->label('TimeTec Patrol (TP)')
                                ->inline(),

                            Checkbox::make('tap')
                                ->label('TimeTec Access (TAP)')
                                ->inline(),

                            Checkbox::make('th')
                                ->label('TimeTec HRMS (TH)')
                                ->inline(),

                            Checkbox::make('tpbi')
                                ->label('TimeTec PBI (TPBI)')
                                ->inline(),
                        ])
                    ]),

                // Section: Implementation Details
                Section::make('Implementation Timeline')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('db_creation')
                                    ->label('DB Creation Date')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y

                                DatePicker::make('kick_off_meeting')
                                    ->label('Kick Off Meeting')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('webinar_training')
                                    ->label('Webinar Training')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y

                                DatePicker::make('go_live_date')
                                    ->label('Go Live Date')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y
                            ]),
                    ]),

                Section::make('Training Information')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('implementer')
                            ->label('Implementer')
                            ->maxLength(255),
                        TextInput::make('payroll_code')
                            ->label('Payroll Code')
                            ->maxLength(50),
                    ]),
                Section::make('Handover Status')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'New' => 'New',
                                'Approved' => 'Approved',
                                'Completed' => 'Completed',
                                'Rejected' => 'Rejected',
                                'Draft' => 'Draft',
                            ])
                            ->default('New')
                            ->required(),

                        Textarea::make('reject_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Enter reason if status is Rejected')
                            ->visible(fn (Forms\Get $get): bool => $get('status') === 'Rejected')
                            ->maxLength(1000),

                        TextInput::make('handover_pdf')
                            ->label('Handover PDF')
                            ->placeholder('PDF will be generated automatically')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('This file is generated when the handover is approved'),
                    ]),
            ]),
            // Section: Status & Approvals

        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query
                    ->where('status', '=', 'Completed')
                    ->orderBy('created_at', 'desc');
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name'),
                    // ->formatStateUsing(function ($state, $record) {
                    //     $fullName = $state ?? $record->company_name ?? 'N/A';

                    //     // Only create the link if lead and lead.id exist
                    //     if ($record->lead && $record->lead->id) {
                    //         $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);
                    //         return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                    //                 target="_blank"
                    //                 title="View lead details"
                    //                 class="inline-block"
                    //                 style="color:#338cf0;">
                    //                 ' . $fullName . '
                    //             </a>';
                    //     }

                    //     // Otherwise, just display the company name without a link
                    //     return $fullName;
                    // })
                    // ->html(),

                    TextColumn::make('ta')
                        ->label('TA')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tl')
                        ->label('TL')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tc')
                        ->label('TC')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tp')
                        ->label('TP')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tap')
                        ->label('TAP')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('th')
                        ->label('TH')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tpbi')
                        ->label('TPBI')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                TextColumn::make('payroll_code')
                    ->label('Payroll Code')
                    ->toggleable(),
                TextColumn::make('company_size')
                    ->label('Company Size')
                    ->toggleable(),
                TextColumn::make('headcount')
                    ->label('Headcount')
                    ->toggleable(),
                TextColumn::make('db_creation')
                    ->label('DB Creation')
                    ->toggleable(),
                TextColumn::make('go_live_date')
                    ->label('Go Live Date')
                    ->toggleable(),
                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(),
                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),
                TextColumn::make('kick_off_meeting')
                    ->label('ON9 Kick Off Meeting')
                    ->toggleable(),
                TextColumn::make('webinar_training')
                    ->label('ON9 webinar Training')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('created_at')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('')
                        ->placeholder('Select date range'),
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range from the "start - end" format
                        [$start, $end] = explode(' - ', $data['date_range']);

                        // Ensure valid dates
                        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                        // Apply the filter
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->indicateUsing(function (array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range for display
                        [$start, $end] = explode(' - ', $data['date_range']);

                        return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                            ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                    }
                    return null;
                }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         //
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoftwareHandovers::route('/'),
            // 'create' => Pages\CreateSoftwareHandover::route('/create'),
            'edit' => Pages\EditSoftwareHandover::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
