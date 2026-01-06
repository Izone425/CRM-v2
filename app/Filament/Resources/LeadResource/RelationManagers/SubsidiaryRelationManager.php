<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\Industry;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class SubsidiaryRelationManager extends RelationManager
{
    protected static string $relationship = 'subsidiaries';

    #[On('refresh-quotations')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        $leadCompany = $this->ownerRecord->companyDetail;
        $leadEInvoice = $this->ownerRecord->eInvoiceDetail;

        return [
            Grid::make(2)
                ->schema([
                    Section::make('Company Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('COMPANY NAME')
                                        ->required()
                                        ->maxLength(255)
                                        ->default(fn() => $leadCompany ? $leadCompany->company_name : null)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                ]),
                        ])
                        ->columnSpan(2),

                    Section::make('Address Information')
                        ->schema([
                            TextInput::make('company_address1')
                                ->label('ADDRESS 1')
                                ->required()
                                ->default(fn() => $leadCompany ? $leadCompany->company_address1 : null)
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('company_address2')
                                ->label('ADDRESS 2')
                                ->default(fn() => $leadCompany ? $leadCompany->company_address2 : null)
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            Grid::make(3)
                                ->schema([
                                    TextInput::make('postcode')
                                        ->label('POSTCODE')
                                        ->required()
                                        ->default(fn() => $leadCompany ? $leadCompany->postcode : null)
                                        ->maxLength(5)
                                        ->rules(['regex:/^[0-9]+$/'])
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    TextInput::make('city')
                                        ->label('CITY')
                                        ->required()
                                        ->default(fn() => $leadCompany ? $leadCompany->city : $this->ownerRecord->city)
                                        ->maxLength(255)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    Select::make('state')
                                        ->label('STATE')
                                        ->required()
                                        ->default(fn() => $leadCompany ? $leadCompany->state : null)
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/StateCodes.json');
                                            if (file_exists($filePath)) {
                                                $statesContent = file_get_contents($filePath);
                                                $states = json_decode($statesContent, true);
                                                return collect($states)->mapWithKeys(function ($state) {
                                                    return [$state['Code'] => ucfirst(strtolower($state['State']))];
                                                })->toArray();
                                            }
                                            return ['10' => 'Selangor'];
                                        })
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Select::make('country')
                                ->label('COUNTRY')
                                ->required()
                                ->default('MYS')
                                ->options(function () {
                                    $filePath = storage_path('app/public/json/CountryCodes.json');
                                    if (file_exists($filePath)) {
                                        $countriesContent = file_get_contents($filePath);
                                        $countries = json_decode($countriesContent, true);
                                        return collect($countries)->mapWithKeys(function ($country) {
                                            return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                        })->toArray();
                                    }
                                    return ['MYS' => 'Malaysia'];
                                })
                                ->searchable()
                                ->preload(),
                        ])
                        ->columnSpan(1),

                    Section::make('HR Contact Person')
                        ->schema([
                            TextInput::make('name')
                                ->label('NAME')
                                ->default(fn() => $leadCompany->name ? $leadCompany->name : $this->ownerRecord->name)
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('contact_number')
                                ->label('CONTACT NUMBER')
                                ->default(fn() => $leadCompany->contact_no ? $leadCompany->contact_no : $this->ownerRecord->phone)
                                ->required()
                                ->tel()
                                ->maxLength(20)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('email')
                                ->label('EMAIL ADDRESS')
                                ->default(fn() => $leadCompany->email ? $leadCompany->email : $this->ownerRecord->email)
                                ->required()
                                ->email()
                                ->maxLength(255),

                            TextInput::make('position')
                                ->label('POSITION')
                                ->default(fn() => $leadCompany->position ? $leadCompany->position : ($this->ownerRecord->position ?? null))
                                ->required()
                                ->maxLength(100)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(2),

            Grid::make(2)
                ->schema([
                    Section::make('Business Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('currency')
                                        ->label('CURRENCY')
                                        ->required()
                                        ->default('MYR')
                                        ->options([
                                            'MYR' => 'MYR',
                                            'USD' => 'USD',
                                        ])
                                        ->searchable()
                                        ->preload(),

                                    Select::make('business_type')
                                        ->label('BUSINESS TYPE')
                                        ->required()
                                        ->default('local_business')
                                        ->options([
                                            'local_business' => 'Local Business',
                                            'foreign_business' => 'Foreign Business',
                                        ])
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Select::make('business_category')
                                        ->label('BUSINESS CATEGORY')
                                        ->required()
                                        ->default('business')
                                        ->options([
                                            'business' => 'Business',
                                            'government' => 'Government',
                                        ])
                                        ->searchable()
                                        ->preload(),

                                    Select::make('billing_category')
                                        ->label('BILLING CATEGORY')
                                        ->required()
                                        ->default('billing_to_subscriber')
                                        ->options([
                                            'billing_to_subscriber' => 'Billing to Subscriber',
                                            'billing_to_reseller' => 'Billing to Reseller',
                                        ])
                                        ->searchable()
                                        ->preload(),
                                ]),
                        ])
                        ->columnSpan(1),

                    Section::make('Finance Contact Person')
                        ->schema([
                            TextInput::make('finance_person_name')
                                ->label('FINANCE PERSON NAME')
                                ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_name : null)
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('finance_person_email')
                                ->label('FINANCE PERSON EMAIL')
                                ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_email : null)
                                ->required()
                                ->email()
                                ->maxLength(255),

                            TextInput::make('finance_person_contact')
                                ->label('FINANCE PERSON CONTACT')
                                ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_contact : null)
                                ->required()
                                ->maxLength(20)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('finance_person_position')
                                ->label('FINANCE PERSON POSITION')
                                ->default(fn() => $leadEInvoice ? $leadEInvoice->finance_person_position : null)
                                ->required()
                                ->maxLength(100)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(2),
            ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('COMPANY NAME')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('HR CONTACT NAME')
                    ->limit(20),

                Tables\Columns\TextColumn::make('contact_number')
                    ->label('HR CONTACT NO.')
                    ->limit(15),

                Tables\Columns\TextColumn::make('finance_person_name')
                    ->label('FINANCE CONTACT NAME')
                    ->limit(20),

                Tables\Columns\TextColumn::make('finance_person_contact')
                    ->label('FINANCE CONTACT NO.')
                    ->limit(15),

                Tables\Columns\TextColumn::make('state')
                    ->label('STATE')
                    ->limit(10),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('ADDED ON')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Subsidiary')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->form($this->defaultForm())
                    ->action(function (array $data) {
                        $this->ownerRecord->subsidiaries()->create($data);
                        Notification::make()
                            ->title('Subsidiary added successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record) => 'View Subsidiary: ' . $record->company_name),

                    Action::make('edit')
                        ->icon('heroicon-o-pencil-square')
                        ->label('Edit')
                        ->modalHeading(fn($record) => 'Edit Subsidiary: ' . $record->company_name)
                        ->modalWidth('7xl')
                        ->form(function ($record) {
                            // ✅ Return form with pre-filled values from the record
                            return [
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Company Information')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('company_name')
                                                            ->label('COMPANY NAME')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->default($record->company_name)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                                    ]),
                                            ])
                                            ->columnSpan(2),

                                        Section::make('Address Information')
                                            ->schema([
                                                TextInput::make('company_address1')
                                                    ->label('ADDRESS 1')
                                                    ->required()
                                                    ->default($record->company_address1)
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('company_address2')
                                                    ->label('ADDRESS 2')
                                                    ->default($record->company_address2)
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('postcode')
                                                            ->label('POSTCODE')
                                                            ->required()
                                                            ->default($record->postcode)
                                                            ->maxLength(5)
                                                            ->rules(['regex:/^[0-9]+$/'])
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        TextInput::make('city')
                                                            ->label('CITY')
                                                            ->required()
                                                            ->default($record->city)
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        Select::make('state')
                                                            ->label('STATE')
                                                            ->required()
                                                            ->default($record->state)
                                                            ->options(function () {
                                                                $filePath = storage_path('app/public/json/StateCodes.json');
                                                                if (file_exists($filePath)) {
                                                                    $statesContent = file_get_contents($filePath);
                                                                    $states = json_decode($statesContent, true);
                                                                    return collect($states)->mapWithKeys(function ($state) {
                                                                        return [$state['Code'] => ucfirst(strtolower($state['State']))];
                                                                    })->toArray();
                                                                }
                                                                return ['10' => 'Selangor'];
                                                            })
                                                            ->searchable()
                                                            ->preload(),
                                                    ]),

                                                Select::make('country')
                                                    ->label('COUNTRY')
                                                    ->required()
                                                    ->default($record->country ?? 'MYS')
                                                    ->options(function () {
                                                        $filePath = storage_path('app/public/json/CountryCodes.json');
                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);
                                                            return collect($countries)->mapWithKeys(function ($country) {
                                                                return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                            })->toArray();
                                                        }
                                                        return ['MYS' => 'Malaysia'];
                                                    })
                                                    ->searchable()
                                                    ->preload(),
                                            ])
                                            ->columnSpan(1),

                                        Section::make('HR Contact Person')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('NAME')
                                                    ->default($record->name)
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('contact_number')
                                                    ->label('CONTACT NUMBER')
                                                    ->default($record->contact_number)
                                                    ->required()
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('email')
                                                    ->label('EMAIL ADDRESS')
                                                    ->default($record->email)
                                                    ->required()
                                                    ->email()
                                                    ->maxLength(255),

                                                TextInput::make('position')
                                                    ->label('POSITION')
                                                    ->default($record->position)
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                            ])
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),

                                Grid::make(2)
                                    ->schema([
                                        Section::make('Business Information')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('currency')
                                                            ->label('CURRENCY')
                                                            ->required()
                                                            ->default($record->currency ?? 'MYR')
                                                            ->options([
                                                                'MYR' => 'MYR',
                                                                'USD' => 'USD',
                                                            ])
                                                            ->searchable()
                                                            ->preload(),

                                                        Select::make('business_type')
                                                            ->label('BUSINESS TYPE')
                                                            ->required()
                                                            ->default($record->business_type ?? 'local_business')
                                                            ->options([
                                                                'local_business' => 'Local Business',
                                                                'foreign_business' => 'Foreign Business',
                                                            ])
                                                            ->searchable()
                                                            ->preload(),
                                                    ]),

                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('business_category')
                                                            ->label('BUSINESS CATEGORY')
                                                            ->required()
                                                            ->default($record->business_category ?? 'business')
                                                            ->options([
                                                                'business' => 'Business',
                                                                'government' => 'Government',
                                                            ])
                                                            ->searchable()
                                                            ->preload(),

                                                        Select::make('billing_category')
                                                            ->label('BILLING CATEGORY')
                                                            ->required()
                                                            ->default($record->billing_category ?? 'billing_to_subscriber')
                                                            ->options([
                                                                'billing_to_subscriber' => 'Billing to Subscriber',
                                                                'billing_to_reseller' => 'Billing to Reseller',
                                                            ])
                                                            ->searchable()
                                                            ->preload(),
                                                    ]),
                                            ])
                                            ->columnSpan(1),

                                        Section::make('Finance Contact Person')
                                            ->schema([
                                                TextInput::make('finance_person_name')
                                                    ->label('FINANCE PERSON NAME')
                                                    ->default($record->finance_person_name)
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('finance_person_email')
                                                    ->label('FINANCE PERSON EMAIL')
                                                    ->default($record->finance_person_email)
                                                    ->required()
                                                    ->email()
                                                    ->maxLength(255),

                                                TextInput::make('finance_person_contact')
                                                    ->label('FINANCE PERSON CONTACT')
                                                    ->default($record->finance_person_contact)
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('finance_person_position')
                                                    ->label('FINANCE PERSON POSITION')
                                                    ->default($record->finance_person_position)
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                            ])
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ];
                        })
                        ->action(function ($record, array $data) {
                            // Convert all data to uppercase except email and specific fields
                            foreach ($data as $key => $value) {
                                if (is_string($value) && !in_array($key, ['email', 'finance_person_email', 'business_type', 'business_category', 'billing_category'])) {
                                    $data[$key] = Str::upper($value);
                                }
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('Subsidiary updated successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('delete')
                        ->icon('heroicon-o-trash')
                        ->label('Delete')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => 'Delete Subsidiary: ' . $record->company_name)
                        ->modalDescription('Are you sure you want to delete this subsidiary? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete subsidiary')
                        ->action(function ($record) {
                            $record->delete();

                            Notification::make()
                                ->title('Subsidiary deleted successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
