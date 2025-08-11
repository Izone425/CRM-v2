<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\ImplementerNote;
use App\Models\SoftwareHandover;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Actions\ActionGroup;
use Filament\Forms\Components\Badge;
use Filament\Forms\Components\Button;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class ImplementerPICTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    // Group 1: Original PICs from Software Handover
                    Section::make(function ($record) {
                        // Get the software handover ID if available
                        $swHandover = SoftwareHandover::where('lead_id', $record->id)
                            ->orderBy('created_at', 'desc')  // Order by creation date, most recent first
                            ->first();
                        $handoverId = $swHandover ? "SW_" . str_pad($swHandover->id, 6, '0', STR_PAD_LEFT) : "N/A";

                        return 'Original Details from Software Handover ID ' . $handoverId;
                    })
                    ->description('Original implementation PICs from software handover')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        // Display original PICs in a custom view
                        View::make('components.original-pic-cards')
                    ])
                    ->headerActions([
                        Action::make('update_original_pic_status')
                            ->label('Update Status')
                            ->color('danger')
                            ->modalWidth('5xl')
                            ->modalHeading('Update Original PIC Status')
                            ->modalDescription('You can only update the status of original PICs to "Resign". All other fields cannot be edited.')
                            ->form(function ($record) {
                                // Get the software handover for this lead
                                $swHandover = SoftwareHandover::where('lead_id', $record->id)
                                ->orderBy('created_at', 'desc')  // Order by creation date, most recent first
                                ->first();

                                if (!$swHandover || !$swHandover->implementation_pics) {
                                    return [
                                        Section::make('No PICs Found')
                                            ->schema([
                                                View::make('components.empty-state-message')
                                                    ->viewData([
                                                        'message' => 'No original PICs found in the software handover.'
                                                    ])
                                            ])
                                    ];
                                }

                                $originalPics = [];
                                if (is_string($swHandover->implementation_pics)) {
                                    $originalPics = json_decode($swHandover->implementation_pics, true) ?? [];
                                } else {
                                    $originalPics = $swHandover->implementation_pics ?? [];
                                }

                                $formComponents = [];

                                foreach ($originalPics as $index => $pic) {
                                    $formComponents[] = Section::make('PIC #' . ($index + 1))
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    TextInput::make("original_pics.{$index}.pic_name_impl")
                                                        ->label('Name')
                                                        ->default($pic['pic_name_impl'] ?? '')
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->disabled(),

                                                    TextInput::make("original_pics.{$index}.position")
                                                        ->label('Position')
                                                        ->default($pic['position'] ?? '')
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->disabled(),

                                                    TextInput::make("original_pics.{$index}.pic_phone_impl")
                                                        ->label('Phone Number')
                                                        ->default($pic['pic_phone_impl'] ?? '')
                                                        ->tel()
                                                        ->disabled(),

                                                    TextInput::make("original_pics.{$index}.pic_email_impl")
                                                        ->label('Email')
                                                        ->default($pic['pic_email_impl'] ?? '')
                                                        ->disabled(),

                                                    Select::make("original_pics.{$index}.status")
                                                        ->label('Status')
                                                        ->options([
                                                            'Available' => 'Available',
                                                            'Resign' => 'Resign'
                                                        ])
                                                        ->default($pic['status'] ?? 'Available')
                                                        ->required(),
                                                ]),
                                            // Hidden field to store the original data
                                            TextInput::make("original_pics.{$index}.original_data")
                                                ->default(json_encode($pic))
                                                ->hidden(),
                                        ]);
                                }

                                return $formComponents;
                            })
                            ->action(function ($data, Lead $record) {
                                $swHandover = SoftwareHandover::where('lead_id', $record->id)
                                        ->orderBy('created_at', 'desc')  // Order by creation date, most recent first
                                        ->first();

                                if (!$swHandover) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Software handover record not found')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                try {
                                    // Get the original implementation_pics
                                    $originalPics = [];
                                    if (is_string($swHandover->implementation_pics)) {
                                        $originalPics = json_decode($swHandover->implementation_pics, true) ?? [];
                                    } else {
                                        $originalPics = $swHandover->implementation_pics ?? [];
                                    }

                                    // Update only the status field for each PIC
                                    foreach ($data['original_pics'] as $index => $picData) {
                                        // Check if original_data exists before trying to use it
                                        if (isset($picData['original_data'])) {
                                            // Restore the original data and update only the status
                                            $originalData = json_decode($picData['original_data'], true);
                                            $originalData['status'] = $picData['status'];
                                            $originalPics[$index] = $originalData;
                                        } else {
                                            // If original_data is missing, just update the status directly
                                            if (isset($originalPics[$index])) {
                                                $originalPics[$index]['status'] = $picData['status'];
                                            } else {
                                                // Create minimal record with status if nothing exists at this index
                                                $originalPics[$index] = [
                                                    'status' => $picData['status'],
                                                    'pic_name_impl' => $picData['pic_name_impl'] ?? 'Unknown',
                                                    'position' => $picData['position'] ?? '',
                                                    'pic_phone_impl' => $picData['pic_phone_impl'] ?? '',
                                                    'pic_email_impl' => $picData['pic_email_impl'] ?? '',
                                                ];
                                            }
                                        }
                                    }

                                    // Save the updated PICs
                                    $swHandover->update([
                                        'implementation_pics' => $originalPics
                                    ]);

                                    Notification::make()
                                        ->title('PIC status updated successfully')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Failed to update original PIC status: ' . $e->getMessage(), [
                                        'lead_id' => $record->id,
                                        'exception' => $e
                                    ]);

                                    Notification::make()
                                        ->title('Error updating PIC status')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ]),

                    // Group 2: New PICs added by Implementer
                    Section::make('New Implementation PIC added by Implementer')
                        ->description('Add additional persons in charge for this lead')
                        ->icon('heroicon-o-users')
                        ->schema([
                            // Display saved new PICs in a card view
                            View::make('components.new-pic-cards')
                        ])
                        ->headerActions([
                            Action::make('add_new_pic')
                                ->label('Add New PIC Details')
                                ->color('primary')
                                ->modalWidth('5xl')
                                ->form([
                                    // Add the repeater directly in the section
                                    Repeater::make('additional_pic')
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->columnSpan(1),

                                                    TextInput::make('position')
                                                        ->maxLength(255)
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->columnSpan(1),

                                                    TextInput::make('hp_number')
                                                        ->required()
                                                        ->tel()
                                                        ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                        ->maxLength(20)
                                                        ->columnSpan(1),

                                                    TextInput::make('email')
                                                        ->required()
                                                        ->email()
                                                        ->maxLength(255)
                                                        ->columnSpan(1),

                                                    Select::make('status')
                                                        ->options([
                                                            'Available' => 'Available',
                                                        ])
                                                        ->default('Available')
                                                        ->required()
                                                        ->columnSpan(1),
                                                ]),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                        ->collapsible()
                                        ->createItemButtonLabel('Add PIC')
                                        ->defaultItems(1),
                                ])
                                ->action(function (Lead $record, array $data) {
                                    if (!$record->companyDetail) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Company details are required before adding additional contacts')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        // Get existing PICs
                                        $existingPics = [];
                                        if (!empty($record->companyDetail->additional_pic)) {
                                            if (is_string($record->companyDetail->additional_pic)) {
                                                $existingPics = json_decode($record->companyDetail->additional_pic, true) ?? [];
                                            } else {
                                                $existingPics = $record->companyDetail->additional_pic ?? [];
                                            }
                                        }

                                        // Add new PICs
                                        $allPics = array_merge($existingPics, $data['additional_pic'] ?? []);

                                        // Save to company details
                                        $record->companyDetail->update([
                                            'additional_pic' => json_encode($allPics)
                                        ]);

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Added new implementation PICs for lead');

                                        Notification::make()
                                            ->title('New PICs added successfully')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Failed to save new PICs: ' . $e->getMessage(), [
                                            'lead_id' => $record->id,
                                            'exception' => $e
                                        ]);

                                        Notification::make()
                                            ->title('Error saving contacts')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),

                            Action::make('update_new_pic_status')
                                ->label('Update Status')
                                ->color('danger')
                                ->modalWidth('5xl')
                                ->modalHeading('Update New PIC Status')
                                ->modalDescription('You can only update the status of new PICs.')
                                ->form(function ($record) {
                                    if (!$record || !$record->companyDetail || empty($record->companyDetail->additional_pic)) {
                                        return [
                                            Section::make('No PICs Found')
                                                ->schema([
                                                    View::make('components.empty-state-message')
                                                        ->viewData([
                                                            'message' => 'No additional PICs found for this lead.'
                                                        ])
                                                ])
                                        ];
                                    }

                                    $additionalPics = [];
                                    if (is_string($record->companyDetail->additional_pic)) {
                                        $additionalPics = json_decode($record->companyDetail->additional_pic, true) ?? [];
                                    } else {
                                        $additionalPics = $record->companyDetail->additional_pic ?? [];
                                    }

                                    $formComponents = [];

                                    foreach ($additionalPics as $index => $pic) {
                                        $formComponents[] = Section::make('PIC #' . ($index + 1))
                                            ->schema([
                                                Grid::make(5)
                                                    ->schema([
                                                        TextInput::make("new_pics.{$index}.name")
                                                            ->label('Name')
                                                            ->default($pic['name'] ?? '')
                                                            ->disabled(),

                                                        TextInput::make("new_pics.{$index}.position")
                                                            ->label('Position')
                                                            ->default($pic['position'] ?? '')
                                                            ->disabled(),

                                                        TextInput::make("new_pics.{$index}.hp_number")
                                                            ->label('Phone Number')
                                                            ->default($pic['hp_number'] ?? '')
                                                            ->disabled(),

                                                        TextInput::make("new_pics.{$index}.email")
                                                            ->label('Email')
                                                            ->default($pic['email'] ?? '')
                                                            ->disabled(),

                                                        Select::make("new_pics.{$index}.status")
                                                            ->label('Status')
                                                            ->options([
                                                                'Available' => 'Available',
                                                                'Resign' => 'Resign'
                                                            ])
                                                            ->default($pic['status'] ?? 'Available')
                                                            ->required(),
                                                    ]),
                                                // Hidden field to store the original data
                                                TextInput::make("new_pics.{$index}.original_data")
                                                    ->default(json_encode($pic))
                                                    ->hidden(),
                                            ]);
                                    }

                                    return $formComponents;
                                })
                                ->action(function ($data, Lead $record) {
                                    if (!$record->companyDetail) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Company details not found')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        // Get the original additional_pic
                                        $additionalPics = [];
                                        if (is_string($record->companyDetail->additional_pic)) {
                                            $additionalPics = json_decode($record->companyDetail->additional_pic, true) ?? [];
                                        } else {
                                            $additionalPics = $record->companyDetail->additional_pic ?? [];
                                        }

                                        // Update only the status field for each PIC
                                        foreach ($data['new_pics'] as $index => $picData) {
                                            // Check if original_data exists before trying to use it
                                            if (isset($picData['original_data'])) {
                                                // Restore the original data and update only the status
                                                $originalData = json_decode($picData['original_data'], true);
                                                $originalData['status'] = $picData['status'];
                                                $additionalPics[$index] = $originalData;
                                            } else {
                                                // If original_data is missing, just update the status directly
                                                if (isset($additionalPics[$index])) {
                                                    $additionalPics[$index]['status'] = $picData['status'];
                                                } else {
                                                    // Create minimal record with status if nothing exists at this index
                                                    $additionalPics[$index] = [
                                                        'status' => $picData['status'],
                                                        'name' => $picData['name'] ?? 'Unknown',
                                                        'position' => $picData['position'] ?? '',
                                                        'hp_number' => $picData['hp_number'] ?? '',
                                                        'email' => $picData['email'] ?? '',
                                                    ];
                                                }
                                            }
                                        }

                                        // Save the updated PICs
                                        $record->companyDetail->update([
                                            'additional_pic' => json_encode($additionalPics)
                                        ]);

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Updated new PIC status');

                                        Notification::make()
                                            ->title('PIC status updated successfully')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Failed to update new PIC status: ' . $e->getMessage(), [
                                            'lead_id' => $record->id,
                                            'exception' => $e
                                        ]);

                                        Notification::make()
                                            ->title('Error updating PIC status')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                        ]),
                ]),
        ];
    }
}
