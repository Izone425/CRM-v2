<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSettingResource\Pages;
use App\Models\PaymentSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;

class PaymentSettingResource extends Resource
{
    protected static ?string $model = PaymentSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        // Allow access for Master Admin (role_id = 3) OR users with access to users index
        return $user->role_id == 3 || $user->hasRouteAccess('filament.admin.resources.users.index');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Payment Settings')
                    ->tabs([
                        // Tab 1: Website Information
                        Tabs\Tab::make('Website Information')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Company Details')
                                    ->description('Configure your company information that will appear on invoices and customer communications')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g., TimeTec Cloud Sdn Bhd'),

                                        Forms\Components\TextInput::make('website_url')
                                            ->label('Website URL')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255)
                                            ->placeholder('www.timeteccloud.com'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('support_email')
                                                    ->label('Support Email')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('support@timeteccloud.com'),

                                                Forms\Components\TextInput::make('support_phone')
                                                    ->label('Support Phone')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->placeholder('+60 3-8023 8080'),
                                            ]),

                                        Forms\Components\Textarea::make('company_address')
                                            ->label('Company Address')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('123 Business Street, 50450 Kuala Lumpur, Malaysia'),

                                        Forms\Components\FileUpload::make('company_logo')
                                            ->label('Company Logo')
                                            ->image()
                                            ->directory('company-logos')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->helperText('Upload your company logo (Max: 2MB, Recommended: 300x100px)'),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 2: Payment Gateway
                        Tabs\Tab::make('Payment Gateway')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Payment Gateway Configuration')
                                    ->description('Configure your preferred payment gateway for processing online payments')
                                    ->schema([
                                        Forms\Components\Select::make('payment_gateway')
                                            ->label('Payment Gateway')
                                            ->required()
                                            ->options([
                                                'manual' => 'Manual Payment (Bank Transfer)',
                                                'stripe' => 'Stripe',
                                                'paypal' => 'PayPal',
                                                'billplz' => 'Billplz (Malaysia)',
                                                'ipay88' => 'iPay88 (Malaysia)',
                                                'senangpay' => 'SenangPay (Malaysia)',
                                                'molpay' => 'MOLPay (Malaysia)',
                                            ])
                                            ->default('manual')
                                            ->live()
                                            ->helperText('Select the payment gateway you want to use'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('gateway_api_key')
                                                    ->label('API Key')
                                                    ->password()
                                                    ->revealable()
                                                    ->maxLength(255)
                                                    ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                                    ->helperText('Your payment gateway API key'),

                                                Forms\Components\TextInput::make('gateway_secret_key')
                                                    ->label('Secret Key')
                                                    ->password()
                                                    ->revealable()
                                                    ->maxLength(255)
                                                    ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                                    ->helperText('Your payment gateway secret key'),
                                            ]),

                                        Forms\Components\TextInput::make('gateway_merchant_id')
                                            ->label('Merchant ID')
                                            ->maxLength(255)
                                            ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                            ->helperText('Your merchant ID (if required by gateway)'),

                                        Forms\Components\Toggle::make('gateway_test_mode')
                                            ->label('Test Mode')
                                            ->default(true)
                                            ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                            ->helperText('Enable test mode for development/testing'),

                                        Forms\Components\TextInput::make('gateway_webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->maxLength(255)
                                            ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                            ->helperText('Webhook URL for payment notifications')
                                            ->placeholder(url('/webhooks/payment')),

                                        Forms\Components\KeyValue::make('gateway_settings')
                                            ->label('Additional Gateway Settings')
                                            ->hidden(fn (Forms\Get $get) => $get('payment_gateway') === 'manual')
                                            ->helperText('Any additional configuration required by your payment gateway'),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 3: Invoice Information
                        Tabs\Tab::make('Invoice Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Invoice Settings')
                                    ->description('Configure invoice numbering, terms, and display settings')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('invoice_prefix')
                                                    ->label('Invoice Prefix')
                                                    ->required()
                                                    ->default('INV')
                                                    ->maxLength(10)
                                                    ->placeholder('INV'),

                                                Forms\Components\TextInput::make('invoice_next_number')
                                                    ->label('Next Invoice Number')
                                                    ->required()
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->helperText('The next invoice number to be generated'),

                                                Forms\Components\TextInput::make('invoice_due_days')
                                                    ->label('Payment Due Days')
                                                    ->required()
                                                    ->numeric()
                                                    ->default(30)
                                                    ->suffix('days')
                                                    ->helperText('Default payment due days'),
                                            ]),

                                        Forms\Components\TextInput::make('invoice_number_format')
                                            ->label('Invoice Number Format')
                                            ->required()
                                            ->default('INV-{YEAR}-{MONTH}-{NUMBER}')
                                            ->maxLength(100)
                                            ->helperText('Available placeholders: {PREFIX}, {YEAR}, {MONTH}, {NUMBER}')
                                            ->placeholder('INV-{YEAR}-{MONTH}-{NUMBER}'),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('invoice_currency')
                                                    ->label('Currency')
                                                    ->required()
                                                    ->options([
                                                        'MYR' => 'Malaysian Ringgit (MYR)',
                                                        'SGD' => 'Singapore Dollar (SGD)',
                                                        'USD' => 'US Dollar (USD)',
                                                        'EUR' => 'Euro (EUR)',
                                                        'GBP' => 'British Pound (GBP)',
                                                        'AUD' => 'Australian Dollar (AUD)',
                                                        'THB' => 'Thai Baht (THB)',
                                                        'IDR' => 'Indonesian Rupiah (IDR)',
                                                    ])
                                                    ->default('MYR')
                                                    ->searchable(),

                                                Forms\Components\TextInput::make('invoice_tax_rate')
                                                    ->label('Tax Rate')
                                                    ->numeric()
                                                    ->default(0.00)
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->step(0.01)
                                                    ->helperText('Default tax percentage'),

                                                Forms\Components\TextInput::make('invoice_tax_label')
                                                    ->label('Tax Label')
                                                    ->default('SST')
                                                    ->maxLength(50)
                                                    ->placeholder('SST, GST, VAT, etc.')
                                                    ->helperText('Tax label for invoices'),
                                            ]),

                                        Forms\Components\Textarea::make('invoice_terms')
                                            ->label('Invoice Terms & Conditions')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->placeholder('Payment is due within 30 days from invoice date...')
                                            ->helperText('Terms and conditions that appear on invoices'),

                                        Forms\Components\Textarea::make('invoice_footer')
                                            ->label('Invoice Footer')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Thank you for your business!')
                                            ->helperText('Footer text that appears at the bottom of invoices'),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 4: Commission Settings
                        Tabs\Tab::make('Commission Settings')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Commission Configuration')
                                    ->description('Configure commission rates and payout settings for resellers, distributors, and referrals')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('commission_type')
                                                    ->label('Commission Type')
                                                    ->required()
                                                    ->options([
                                                        'percentage' => 'Percentage (%)',
                                                        'fixed' => 'Fixed Amount',
                                                    ])
                                                    ->default('percentage')
                                                    ->live()
                                                    ->helperText('How commission is calculated'),

                                                Forms\Components\Select::make('commission_calculation')
                                                    ->label('Calculate Commission On')
                                                    ->required()
                                                    ->options([
                                                        'net' => 'Net Amount (after tax)',
                                                        'gross' => 'Gross Amount (before tax)',
                                                    ])
                                                    ->default('net')
                                                    ->helperText('Basis for commission calculation'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('commission_rate')
                                                    ->label('Default Commission Rate')
                                                    ->numeric()
                                                    ->default(0.00)
                                                    ->suffix(fn (Forms\Get $get) => $get('commission_type') === 'percentage' ? '%' : '')
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->helperText('Default commission rate for all sales'),

                                                Forms\Components\TextInput::make('commission_payout_days')
                                                    ->label('Commission Payout Days')
                                                    ->required()
                                                    ->numeric()
                                                    ->default(30)
                                                    ->suffix('days')
                                                    ->minValue(0)
                                                    ->helperText('Days after invoice payment to pay commission'),
                                            ]),

                                        Forms\Components\Fieldset::make('Commission Rates by Type')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('reseller_commission_rate')
                                                            ->label('Reseller Commission')
                                                            ->numeric()
                                                            ->default(0.00)
                                                            ->suffix(fn (Forms\Get $get) => $get('commission_type') === 'percentage' ? '%' : '')
                                                            ->minValue(0)
                                                            ->step(0.01),

                                                        Forms\Components\TextInput::make('distributor_commission_rate')
                                                            ->label('Distributor Commission')
                                                            ->numeric()
                                                            ->default(0.00)
                                                            ->suffix(fn (Forms\Get $get) => $get('commission_type') === 'percentage' ? '%' : '')
                                                            ->minValue(0)
                                                            ->step(0.01),

                                                        Forms\Components\TextInput::make('referral_commission_rate')
                                                            ->label('Referral Commission')
                                                            ->numeric()
                                                            ->default(0.00)
                                                            ->suffix(fn (Forms\Get $get) => $get('commission_type') === 'percentage' ? '%' : '')
                                                            ->minValue(0)
                                                            ->step(0.01),
                                                    ]),
                                            ]),

                                        Forms\Components\KeyValue::make('tier_based_commission')
                                            ->label('Tier-Based Commission Structure')
                                            ->keyLabel('Sales Tier (e.g., 0-10000)')
                                            ->valueLabel('Commission Rate')
                                            ->helperText('Define commission rates based on sales tiers (optional)')
                                            ->addActionLabel('Add Tier'),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),

                // Hidden audit fields
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),

                Forms\Components\Hidden::make('updated_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_gateway')
                    ->label('Payment Gateway')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'billplz' => 'Billplz',
                        'ipay88' => 'iPay88',
                        'senangpay' => 'SenangPay',
                        'molpay' => 'MOLPay',
                        'manual' => 'Manual Payment',
                        default => 'Unknown',
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'manual' ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('invoice_currency')
                    ->label('Currency')
                    ->badge(),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Default Commission')
                    ->suffix('%')
                    ->numeric(2),

                Tables\Columns\IconColumn::make('gateway_test_mode')
                    ->label('Test Mode')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->label('Payment Gateway')
                    ->options([
                        'manual' => 'Manual Payment',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'billplz' => 'Billplz',
                        'ipay88' => 'iPay88',
                        'senangpay' => 'SenangPay',
                        'molpay' => 'MOLPay',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPaymentSettings::route('/'),
            'create' => Pages\CreatePaymentSetting::route('/create'),
            'edit' => Pages\EditPaymentSetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
