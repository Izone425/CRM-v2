<?php

namespace App\Filament\Pages;

use App\Models\ResellerV2;
use App\Models\Reseller;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Services\IrbmService;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Illuminate\Support\Facades\Log;

class ResellerAccount extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Reseller Accounts';
    protected static ?string $title = 'Reseller Accounts';
    protected static string $view = 'filament.pages.reseller-account';
    protected static ?int $navigationSort = 70;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createReseller')
                ->label('Create Reseller')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl')
                ->slideOver()
                ->form([
                    Select::make('company_name')
                        ->label('Company Name')
                        ->searchable()
                        ->required()
                        ->options(function () {
                            return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                ->table('crm_reseller_link')
                                ->whereNotNull('reseller_name')
                                ->where('reseller_name', '!=', '')
                                ->orderBy('reseller_name')
                                ->pluck('reseller_name', 'reseller_name')
                                ->toArray();
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $reseller = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->where('reseller_name', $state)
                                    ->first();

                                if ($reseller) {
                                    $set('reseller_id', $reseller->reseller_id);
                                }
                            }
                        }),

                    Grid::make(3)
                        ->schema([
                                TextInput::make('name')
                                    ->label('PIC Name')
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toUpperCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('PIC No HP')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('pic_email')
                                    ->label('PIC Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('business_register_number')
                                ->label('Business Register Number')
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                ->rules([
                                    'regex:/^[A-Z0-9\s]+$/i',
                                ])
                                ->validationMessages([
                                    'regex' => 'Business Register Number can only contain letters, numbers, and spaces.',
                                ])
                                ->required()
                                ->maxLength(12)
                                ->suffixAction(
                                    ActionsAction::make('searchTin')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(function ($state, $set, $get) {
                                            if (empty($state)) {
                                                Notification::make()
                                                    ->title('Business Register Number Required')
                                                    ->body('Please enter a Business Register Number before searching.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            try {
                                                $companyName = $get('company_name') ?? '';

                                                $irbmService = new IrbmService();
                                                $tin = $irbmService->searchTaxPayerTin(
                                                    name: '',
                                                    idType: 'BRN',
                                                    idValue: strtoupper($state)
                                                );

                                                if (!empty($tin)) {
                                                    $set('tax_identification_number', $tin);

                                                    Notification::make()
                                                        ->title('TIN Found')
                                                        ->body("Tax Identification Number: {$tin}")
                                                        ->success()
                                                        ->send();

                                                    Log::channel('irbm_log')->info("TIN found for BRN {$state}: {$tin}");
                                                } else {
                                                    Notification::make()
                                                        ->title('TIN Not Found')
                                                        ->body('No Tax Identification Number found for this Business Register Number.')
                                                        ->warning()
                                                        ->send();

                                                    Log::channel('irbm_log')->warning("No TIN found for BRN: {$state}");
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Search Failed')
                                                    ->body('Failed to search TIN: ' . $e->getMessage())
                                                    ->danger()
                                                    ->send();

                                                Log::channel('irbm_log')->error('TIN search error: ' . $e->getMessage());
                                            }
                                        })
                                ),

                            TextInput::make('tax_identification_number')
                                ->label('Tax Identification Number')
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                ->maxLength(255),
                        ]),

                    Select::make('sst_category')
                        ->label('SST Category')
                        ->options([
                            'EXEMPTED' => 'Exempted',
                            'NON-EXEMPTED' => 'Non-Exempted',
                        ])
                        ->required()
                        ->default('NON-EXEMPTED'),

                    TextInput::make('commission_rate')
                        ->label('Commission Scheme (%)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(99.99)
                        ->step(0.01)
                        ->suffix('%'),

                    TextInput::make('reseller_id')
                        ->label('Bind Reseller ID (Admin Portal)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-filled when company is selected'),
                ])
                ->action(function (array $data) {
                    // Auto-generate password
                    $password = Str::random(12);

                    // Create reseller with PIC email
                    $reseller = ResellerV2::create([
                        'company_name' => $data['company_name'],
                        'name' => $data['name'],
                        'phone' => $data['phone'],
                        'email' => $data['pic_email'],
                        'password' => Hash::make($password),
                        'plain_password' => $password,
                        'ssm_number' => $data['business_register_number'],
                        'tax_identification_number' => $data['tax_identification_number'] ?? null,
                        'sst_category' => $data['sst_category'],
                        'commission_rate' => $data['commission_rate'],
                        'reseller_id' => $data['reseller_id'] ?? null,
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Reseller Created')
                        ->body("Reseller created successfully. Login email: {$data['pic_email']}, Password: {$password}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerV2::query())
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('PIC Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('PIC No HP')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('PIC Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('plain_password')
                    ->label('Password')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('ssm_number')
                    ->label('SSM Number')
                    ->searchable(),

                TextColumn::make('tax_identification_number')
                    ->label('Tax ID Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sst_category')
                    ->label('SST Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EXEMPTED' => 'success',
                        'NON-EXEMPTED' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('commission_rate')
                    ->label('Commission %')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%'),

                TextColumn::make('reseller.company_name')
                    ->label('Bound Reseller')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}

