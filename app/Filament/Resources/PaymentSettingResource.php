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

    // Hide from Filament navigation (shown in custom sidebar only)
    protected static bool $shouldRegisterNavigation = false;

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
                                Forms\Components\Section::make('Website Information Details')
                                    ->description('Configure your website information and settings')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('website_name')
                                                    ->label('Website Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('TimeTec'),

                                                Forms\Components\TextInput::make('website_url')
                                                    ->label('Website URL')
                                                    ->url()
                                                    ->prefix('https://')
                                                    ->maxLength(255)
                                                    ->placeholder('www.timeteccloud.com'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('admin_email')
                                                    ->label('Admin Email')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('support@timeteccloud.com'),

                                                Forms\Components\TextInput::make('disallow_public_email')
                                                    ->label('Disallow Public Email')
                                                    ->maxLength(500)
                                                    ->placeholder('ymail.com, @me.com, msn.com, facebook.com, mailinator.com')
                                                    ->helperText('Comma-separated list of email domains or addresses to block'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('currency_order_page')
                                                    ->label('Currency Used for Order Page')
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
                                                    ->default('USD')
                                                    ->searchable(),

                                                Forms\Components\Radio::make('disallow_same_ip_signup')
                                                    ->label('Disallow Same IP Address Signup')
                                                    ->options([
                                                        1 => 'Yes',
                                                        0 => 'No',
                                                    ])
                                                    ->default(0)
                                                    ->inline()
                                                    ->required(),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 2: Payment Gateway
                        Tabs\Tab::make('Payment Gateway')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Payment Gateway Configuration')
                                    ->description('Configure PayPal payment gateway settings')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('paypal_url')
                                                    ->label('PayPal URL')
                                                    ->url()
                                                    ->maxLength(255)
                                                    ->placeholder('https://www.paypal.com/cgi-bin/webscr')
                                                    ->helperText('PayPal payment processing URL'),

                                                Forms\Components\TextInput::make('paypal_email')
                                                    ->label('PayPal Email Address')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('admin@epicamera.com')
                                                    ->helperText('Your PayPal account email address'),
                                            ]),

                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Radio::make('paypal_enable')
                                                            ->label(new \Illuminate\Support\HtmlString('
                                                                Enable
                                                                <svg class="inline-block w-4 h-4 ml-1 text-gray-400 cursor-help" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" title="Make sure your enable the account to allow payment to be made directly to your account.">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                                                                </svg>
                                                            '))
                                                            ->options([
                                                                1 => 'Yes',
                                                                0 => 'No',
                                                            ])
                                                            ->default(1)
                                                            ->inline()
                                                            ->required(),
                                                    ]),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 3: Invoice Information
                        Tabs\Tab::make('Invoice Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Invoice Company Details')
                                    ->description('Configure company information that appears on invoices')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_title')
                                            ->label('Invoice Title')
                                            ->maxLength(255)
                                            ->placeholder('TimeTec License Purchase'),

                                        Forms\Components\TextInput::make('invoice_company_name')
                                            ->label('Company Name')
                                            ->maxLength(255)
                                            ->placeholder('TimeTec Computing Sdn Bhd'),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('invoice_company_tel')
                                                    ->label('Company Tel')
                                                    ->tel()
                                                    ->maxLength(50)
                                                    ->placeholder('603 8070 9933'),

                                                Forms\Components\TextInput::make('invoice_fax_no')
                                                    ->label('Fax No')
                                                    ->tel()
                                                    ->maxLength(50)
                                                    ->placeholder('603 8070 9988'),

                                                Forms\Components\TextInput::make('invoice_company_email')
                                                    ->label('Company Email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('info@timeteccloud.com'),
                                            ]),

                                        Forms\Components\Textarea::make('invoice_company_address')
                                            ->label('Company Address')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('No. 6, 8 & 10, Jalan BK 3/2, Bandar Kinrara,'),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('invoice_postcode')
                                                    ->label('Postcode')
                                                    ->maxLength(20)
                                                    ->placeholder('47180'),

                                                Forms\Components\TextInput::make('invoice_city')
                                                    ->label('City')
                                                    ->maxLength(100)
                                                    ->placeholder('Puchong'),

                                                Forms\Components\TextInput::make('invoice_state')
                                                    ->label('State')
                                                    ->maxLength(100)
                                                    ->placeholder('Selangor'),

                                                Forms\Components\Select::make('invoice_country')
                                                    ->label('Country')
                                                    ->options([
                                                        'Malaysia' => 'Malaysia',
                                                        'Singapore' => 'Singapore',
                                                        'Indonesia' => 'Indonesia',
                                                        'Thailand' => 'Thailand',
                                                        'Philippines' => 'Philippines',
                                                        'Vietnam' => 'Vietnam',
                                                        'Other' => 'Other',
                                                    ])
                                                    ->default('Malaysia')
                                                    ->searchable(),
                                            ]),

                                        Forms\Components\FileUpload::make('invoice_company_logo')
                                            ->label('Company Logo')
                                            ->image()
                                            ->directory('invoice-logos')
                                            ->imageEditor()
                                            ->maxSize(5120)
                                            ->helperText('Recommended logo size 5mb'),

                                        Forms\Components\Radio::make('include_bank_details')
                                            ->label('Include Bank Details in Invoice')
                                            ->options([
                                                1 => 'Yes',
                                                0 => 'No',
                                            ])
                                            ->default(1)
                                            ->inline()
                                            ->live()
                                            ->required(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('bank_name')
                                                    ->label('Bank Name')
                                                    ->maxLength(255)
                                                    ->hidden(fn (Forms\Get $get) => !$get('include_bank_details')),

                                                Forms\Components\TextInput::make('bank_account_no')
                                                    ->label('Account No')
                                                    ->maxLength(100)
                                                    ->hidden(fn (Forms\Get $get) => !$get('include_bank_details')),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('bank_beneficiary_name')
                                                    ->label('Beneficiary\'s Name')
                                                    ->maxLength(255)
                                                    ->hidden(fn (Forms\Get $get) => !$get('include_bank_details')),

                                                Forms\Components\TextInput::make('bank_swift_code')
                                                    ->label('Swift Code')
                                                    ->maxLength(50)
                                                    ->hidden(fn (Forms\Get $get) => !$get('include_bank_details')),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        // Tab 4: Commission Settings
                        Tabs\Tab::make('Commission Settings')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Commission Configuration')
                                    ->description('Configure commission percentage rates')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('distributor_commission_rate')
                                                    ->label('DISTRIBUTOR')
                                                    ->options(array_combine(range(0, 100), range(0, 100)))
                                                    ->default(40)
                                                    ->required()
                                                    ->suffix('%'),

                                                Forms\Components\Select::make('dealer_commission_rate')
                                                    ->label('Dealer')
                                                    ->options(array_combine(range(0, 100), range(0, 100)))
                                                    ->default(20)
                                                    ->required()
                                                    ->suffix('%'),
                                            ]),
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
        // No table needed - single settings page
        return $table->columns([]);
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
            'index' => Pages\ManagePaymentSetting::route('/'),
            'edit' => Pages\EditPaymentSetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
