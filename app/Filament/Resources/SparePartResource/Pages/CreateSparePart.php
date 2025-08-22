<?php
namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use App\Models\DeviceModel;
use App\Models\SparePart;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class CreateSparePart extends Page
{
    protected static string $resource = SparePartResource::class;

    protected static string $view = 'filament.resources.spare-part-resource.pages.create-spare-part';

    public ?string $deviceModel = null;
    public array $spareParts = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('deviceModel')
                    ->label('Device Model')
                    ->options(function() {
                        return DeviceModel::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function($state, $set) {
                        if ($state) {
                            $deviceModel = DeviceModel::where('name', $state)->first();
                            // You could pre-fill some info if needed
                        }
                    }),

                Repeater::make('spareParts')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Spare Part Name')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('autocount_code')
                                    ->label('Autocount Code')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->maxLength(255),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->inline(false)
                                    ->default(true),
                            ]),
                    ])
                    ->addActionLabel('Add Spare Part')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(1)
                    ->visible(fn($get) => (bool)$get('deviceModel'))
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (empty($data['deviceModel']) || empty($data['spareParts'])) {
            Notification::make()
                ->title('Please select a device model and add spare parts')
                ->danger()
                ->send();
            return;
        }

        $createdCount = 0;

        foreach ($data['spareParts'] as $partData) {
            // Skip empty rows
            if (empty($partData['name'])) {
                continue;
            }

            $sparePartData = [
                'device_model' => $data['deviceModel'],
                'name' => Str::upper($partData['name']),
                'autocount_code' => $partData['autocount_code'] ? Str::upper($partData['autocount_code']) : null,
                'is_active' => $partData['is_active'],
            ];

            // Create new spare part
            SparePart::create($sparePartData);
            $createdCount++;
        }

        Notification::make()
            ->title($createdCount . ' spare part(s) created successfully')
            ->success()
            ->send();

        // Redirect to index page
        $this->redirect($this->getResource()::getUrl('index'));
    }

    public function addFiveRows(): void
    {
        $currentParts = $this->form->getState()['spareParts'] ?? [];

        for ($i = 0; $i < 5; $i++) {
            $currentParts[] = [
                'name' => '',
                'autocount_code' => '',
                'is_active' => true,
            ];
        }

        $this->form->fill([
            'deviceModel' => $this->deviceModel,
            'spareParts' => $currentParts,
        ]);
    }

    // public function getResource(): string
    // {
    //     return static::$resource;
    // }
}
