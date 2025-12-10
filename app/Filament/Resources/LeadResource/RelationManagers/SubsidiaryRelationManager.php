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

        return [
            Grid::make(3)
                ->schema([
                    Section::make('Company Details')
                        ->schema([
                            TextInput::make('company_name')
                                ->label('COMPANY NAME')
                                ->required()
                                ->maxLength(255)
                                ->default(fn() => $leadCompany ? $leadCompany->company_name : null)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('company_address1')
                                ->label('COMPANY ADDRESS 1')
                                ->default(fn() => $leadCompany ? $leadCompany->company_address1 : null)
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            TextInput::make('company_address2')
                                ->label('COMPANY ADDRESS 2')
                                ->default(fn() => $leadCompany ? $leadCompany->company_address2 : null)
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                            Grid::make(3)
                                ->schema([
                                    Select::make('industry')
                                        ->label('INDUSTRY')
                                        ->placeholder('Select an industry')
                                        ->default(fn() => $leadCompany ? $leadCompany->industry : 'None')
                                        ->options(fn () => collect(['None' => 'None'])->merge(Industry::pluck('name', 'name')))
                                        ->searchable()
                                        ->required(),

                                    TextInput::make('postcode')
                                        ->label('POSTCODE')
                                        ->default(fn() => $leadCompany ? $leadCompany->postcode : null)
                                        ->maxLength(20)
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                        ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                    Select::make('state')
                                        ->label('STATE')
                                        ->default(fn() => $leadCompany ? $leadCompany->state : null)
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/StateCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                return collect($countries)->mapWithKeys(function ($country) {
                                                    return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                })->toArray();
                                            }

                                            return [];
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            $filePath = storage_path('app/public/json/StateCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                foreach ($countries as $country) {
                                                    if ($country['Code'] === $state) {
                                                        return ucfirst(strtolower($country['State']));
                                                    }
                                                }
                                            }

                                            return $state;
                                        })
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                ]),

                            TextInput::make('register_number')
                                ->label('NEW REGISTER NUMBER')
                                ->default(fn() => $leadCompany ? $leadCompany->reg_no_new : null)
                                ->maxLength(50)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                        ])
                        ->columnSpan(2),

                    Section::make('Contact Person')
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

                Tables\Columns\TextColumn::make('register_number')
                    ->label('REG NO.'),

                Tables\Columns\TextColumn::make('name')
                    ->label('CONTACT NAME'),

                Tables\Columns\TextColumn::make('contact_number')
                    ->label('CONTACT NO.'),

                Tables\Columns\TextColumn::make('email')
                    ->label('EMAIL'),

                Tables\Columns\TextColumn::make('industry')
                    ->label('INDUSTRY'),

                Tables\Columns\TextColumn::make('state')
                    ->label('STATE'),

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
                        ->modalWidth('6xl')
                        ->form(function ($record) {
                            // ✅ Return form with pre-filled values from the record
                            return [
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Company Details')
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('COMPANY NAME')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->default($record->company_name) // ✅ Pre-fill with existing value
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('company_address1')
                                                    ->label('COMPANY ADDRESS 1')
                                                    ->default($record->company_address1) // ✅ Pre-fill with existing value
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('company_address2')
                                                    ->label('COMPANY ADDRESS 2')
                                                    ->default($record->company_address2) // ✅ Pre-fill with existing value
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('industry')
                                                            ->label('INDUSTRY')
                                                            ->placeholder('Select an industry')
                                                            ->default($record->industry) // ✅ Pre-fill with existing value
                                                            ->options(fn () => collect(['None' => 'None'])->merge(Industry::pluck('name', 'name')))
                                                            ->searchable()
                                                            ->required(),

                                                        TextInput::make('postcode')
                                                            ->label('POSTCODE')
                                                            ->default($record->postcode) // ✅ Pre-fill with existing value
                                                            ->maxLength(20)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                            ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                        Select::make('state')
                                                            ->label('STATE')
                                                            ->default($record->state) // ✅ Pre-fill with existing value
                                                            ->options(function () {
                                                                $filePath = storage_path('app/public/json/StateCodes.json');

                                                                if (file_exists($filePath)) {
                                                                    $countriesContent = file_get_contents($filePath);
                                                                    $countries = json_decode($countriesContent, true);

                                                                    return collect($countries)->mapWithKeys(function ($country) {
                                                                        return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                                    })->toArray();
                                                                }

                                                                return [];
                                                            })
                                                            ->dehydrateStateUsing(function ($state) {
                                                                $filePath = storage_path('app/public/json/StateCodes.json');

                                                                if (file_exists($filePath)) {
                                                                    $countriesContent = file_get_contents($filePath);
                                                                    $countries = json_decode($countriesContent, true);

                                                                    foreach ($countries as $country) {
                                                                        if ($country['Code'] === $state) {
                                                                            return ucfirst(strtolower($country['State']));
                                                                        }
                                                                    }
                                                                }

                                                                return $state;
                                                            })
                                                            ->required()
                                                            ->searchable()
                                                            ->preload(),
                                                    ]),

                                                TextInput::make('register_number')
                                                    ->label('NEW REGISTER NUMBER')
                                                    ->default($record->register_number) // ✅ Pre-fill with existing value
                                                    ->maxLength(50)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                            ])
                                            ->columnSpan(2),

                                        Section::make('Contact Person')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('NAME')
                                                    ->default($record->name) // ✅ Pre-fill with existing value
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('contact_number')
                                                    ->label('CONTACT NUMBER')
                                                    ->default($record->contact_number) // ✅ Pre-fill with existing value
                                                    ->required()
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),

                                                TextInput::make('email')
                                                    ->label('EMAIL ADDRESS')
                                                    ->default($record->email) // ✅ Pre-fill with existing value
                                                    ->required()
                                                    ->email()
                                                    ->maxLength(255),

                                                TextInput::make('position')
                                                    ->label('POSITION')
                                                    ->default($record->position) // ✅ Pre-fill with existing value
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : null)
                                                    ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : null),
                                            ])
                                            ->columnSpan(1),
                                    ])
                            ];
                        })
                        ->action(function ($record, array $data) {
                            // Convert all data to uppercase except email
                            foreach ($data as $key => $value) {
                                if (is_string($value) && $key !== 'email') {
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
