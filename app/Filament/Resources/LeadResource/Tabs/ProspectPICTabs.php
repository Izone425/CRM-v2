<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\Lead;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProspectPICTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    // Primary PIC Section (from Company Details)
                    Section::make('Primary HR Details')
                        ->schema([
                            // Display primary PIC in a custom view
                            View::make('components.primary-pic-card')
                        ]),

                    // Secondary HR Details Section
                    Section::make('Secondary HR Details')
                        ->schema([
                            // Display saved prospect PICs in a card view
                            View::make('components.prospect-pic-cards')
                        ])
                        ->headerActions([
                            Action::make('add_prospect_pic')
                                ->label('+ HR Details')
                                ->color('primary')
                                ->modalWidth('5xl')
                                ->form([
                                    // Add the repeater directly in the section
                                    Repeater::make('additional_prospect_pic')
                                        ->schema([
                                            Grid::make(5)  // Changed from 5 to 6 columns
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

                                                    TextInput::make('contact_no')
                                                        ->required()
                                                        ->tel()
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
                                                            'Resign' => 'Resign'
                                                        ])
                                                        ->default('Available')
                                                        ->required()
                                                        ->columnSpan(1),
                                                ]),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                        ->collapsible()
                                        ->createItemButtonLabel('Add Contact')
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
                                        // Get existing Prospect PICs
                                        $existingPics = [];
                                        if (!empty($record->companyDetail->additional_prospect_pic)) {
                                            if (is_string($record->companyDetail->additional_prospect_pic)) {
                                                $existingPics = json_decode($record->companyDetail->additional_prospect_pic, true) ?? [];
                                            } else {
                                                $existingPics = $record->companyDetail->additional_prospect_pic ?? [];
                                            }
                                        }

                                        // Check for primary contact selection
                                        $primaryContactSelected = false;
                                        foreach ($data['additional_prospect_pic'] as $key => $picData) {
                                            if (isset($picData['set_as_primary']) && $picData['set_as_primary']) {
                                                $primaryContactSelected = true;

                                                // Get the current primary contact info before replacing it
                                                $currentPrimaryContact = null;
                                                if ($record->companyDetail) {
                                                    $currentPrimaryContact = [
                                                        'name' => $record->companyDetail->name ?? '',
                                                        'position' => $record->companyDetail->position ?? '',
                                                        'email' => $record->companyDetail->email ?? '',
                                                        'contact_no' => $record->companyDetail->contact_no ?? '',
                                                        'status' => 'Available'  // Default status for the swapped contact
                                                    ];
                                                }

                                                // Update the company details with this contact's info
                                                $record->companyDetail->update([
                                                    'name' => $picData['name'],
                                                    'position' => $picData['position'],
                                                    'email' => $picData['email'],
                                                    'contact_no' => $picData['contact_no'],
                                                ]);

                                                // Log the primary contact change
                                                activity()
                                                    ->causedBy(auth()->user())
                                                    ->performedOn($record)
                                                    ->log('Updated primary contact person from additional contacts');

                                                // If the current primary contact has valid data, add it to the additional contacts
                                                if ($currentPrimaryContact && !empty($currentPrimaryContact['name'])) {
                                                    // Make sure we're not adding an empty contact
                                                    if (!empty($currentPrimaryContact['name']) || !empty($currentPrimaryContact['email']) ||
                                                        !empty($currentPrimaryContact['contact_no'])) {
                                                        $existingPics[] = $currentPrimaryContact;
                                                    }
                                                }

                                                // Remove the selected contact from additional contacts (it's now the primary)
                                                unset($data['additional_prospect_pic'][$key]);

                                                break; // Only process the first primary contact found
                                            } else {
                                                // Remove the set_as_primary flag from non-primary contacts
                                                if (isset($data['additional_prospect_pic'][$key]['set_as_primary'])) {
                                                    unset($data['additional_prospect_pic'][$key]['set_as_primary']);
                                                }
                                            }
                                        }

                                        // Add new PICs
                                        $allPics = array_merge($existingPics, $data['additional_prospect_pic'] ?? []);

                                        // Save to company details
                                        $record->companyDetail->update([
                                            'additional_prospect_pic' => json_encode($allPics)
                                        ]);

                                        // Prepare notification message
                                        $message = 'New contacts added successfully';
                                        if ($primaryContactSelected) {
                                            $message .= ' and primary contact updated';
                                        }

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Added new prospect contacts');

                                        Notification::make()
                                            ->title($message)
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Failed to save new prospect contacts: ' . $e->getMessage(), [
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

                            Action::make('update_prospect_pic_status')
                                ->label('Update Status')
                                ->color('danger')
                                ->modalWidth('5xl')
                                ->modalHeading('Update Prospect Contact Status')
                                ->modalDescription('You can only update the status of prospect contacts.')
                                ->visible(function (Lead $record) {
                                    // Check if company detail exists and has additional prospect PICs
                                    if (!$record->companyDetail) {
                                        return false;
                                    }

                                    // Check if additional_prospect_pic exists and is not empty
                                    if (empty($record->companyDetail->additional_prospect_pic)) {
                                        return false;
                                    }

                                    // If it's a string (JSON), decode it and check if it has elements
                                    if (is_string($record->companyDetail->additional_prospect_pic)) {
                                        $pics = json_decode($record->companyDetail->additional_prospect_pic, true);
                                        return !empty($pics);
                                    }

                                    // If it's already an array, check if it has elements
                                    if (is_array($record->companyDetail->additional_prospect_pic)) {
                                        return !empty($record->companyDetail->additional_prospect_pic);
                                    }

                                    // Default to hide if we can't determine
                                    return false;
                                })
                                ->form(function ($record) {
                                    if (!$record || !$record->companyDetail || empty($record->companyDetail->additional_prospect_pic)) {
                                        return [
                                            Section::make('No Contacts Found')
                                                ->schema([
                                                    View::make('components.empty-state-message')
                                                        ->viewData([
                                                            'message' => 'No additional prospect contacts found for this lead.'
                                                        ])
                                                ])
                                        ];
                                    }

                                    $prospectPics = [];
                                    if (is_string($record->companyDetail->additional_prospect_pic)) {
                                        $prospectPics = json_decode($record->companyDetail->additional_prospect_pic, true) ?? [];
                                    } else {
                                        $prospectPics = $record->companyDetail->additional_prospect_pic ?? [];
                                    }

                                    $formComponents = [];

                                    foreach ($prospectPics as $index => $pic) {
                                        $formComponents[] = Section::make('Contact #' . ($index + 1))
                                            ->schema([
                                                Grid::make(6)
                                                    ->schema([
                                                        TextInput::make("prospect_pics.{$index}.name")
                                                            ->label('Name')
                                                            ->default($pic['name'] ?? '')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateUpdated(fn($state) => Str::upper($state)),

                                                        TextInput::make("prospect_pics.{$index}.position")
                                                            ->label('Position')
                                                            ->default($pic['position'] ?? '')
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateUpdated(fn($state) => Str::upper($state)),

                                                        TextInput::make("prospect_pics.{$index}.contact_no")
                                                            ->label('Phone Number')
                                                            ->default($pic['contact_no'] ?? '')
                                                            ->required()
                                                            ->tel()
                                                            ->maxLength(20),

                                                        TextInput::make("prospect_pics.{$index}.email")
                                                            ->label('Email')
                                                            ->default($pic['email'] ?? '')
                                                            ->required()
                                                            ->email()
                                                            ->maxLength(255),

                                                        Select::make("prospect_pics.{$index}.status")
                                                            ->label('Status')
                                                            ->options([
                                                                'Available' => 'Available',
                                                                'Resign' => 'Resign'
                                                            ])
                                                            ->default($pic['status'] ?? 'Available')
                                                            ->required(),

                                                        // Add the Set as Primary checkbox
                                                        Checkbox::make("prospect_pics.{$index}.set_as_primary")
                                                            ->label('Set as Primary')
                                                            ->helperText('Make this the primary contact')
                                                            ->default(false)
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($index) {
                                                                if (!$state) return;

                                                                // Get all the prospect_pics data
                                                                $allPics = $get('prospect_pics');

                                                                // Uncheck all other checkboxes
                                                                foreach ($allPics as $i => $pic) {
                                                                    if ($i == $index) continue; // Skip the current item
                                                                    $set("prospect_pics.{$i}.set_as_primary", false);
                                                                }
                                                            }),
                                                    ]),
                                                // Hidden field to store the original data
                                                TextInput::make("prospect_pics.{$index}.original_data")
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
                                        // Get the original additional_prospect_pic
                                        $prospectPics = [];
                                        if (is_string($record->companyDetail->additional_prospect_pic)) {
                                            $prospectPics = json_decode($record->companyDetail->additional_prospect_pic, true) ?? [];
                                        } else {
                                            $prospectPics = $record->companyDetail->additional_prospect_pic ?? [];
                                        }

                                        // Check for primary contact selection
                                        $primaryContactSelected = false;
                                        $primaryContactIndex = null;

                                        // First update the statuses and check for primary selection
                                        foreach ($data['prospect_pics'] as $index => $picData) {
                                            // Check for primary contact selection
                                            if (isset($picData['set_as_primary']) && $picData['set_as_primary']) {
                                                $primaryContactSelected = true;
                                                $primaryContactIndex = $index;
                                            }

                                            // Update ALL fields (not just status and contact_no)
                                            if (isset($picData['original_data'])) {
                                                // Create updated data with all fields
                                                $updatedData = [
                                                    'name' => strtoupper($picData['name'] ?? ''),
                                                    'position' => strtoupper($picData['position'] ?? ''),
                                                    'contact_no' => $picData['contact_no'] ?? '',
                                                    'email' => $picData['email'] ?? '',
                                                    'status' => $picData['status'] ?? 'Available'
                                                ];

                                                $prospectPics[$index] = $updatedData;
                                            } else {
                                                // If original_data is missing, create a new record with all fields
                                                $prospectPics[$index] = [
                                                    'name' => strtoupper($picData['name'] ?? 'Unknown'),
                                                    'position' => strtoupper($picData['position'] ?? ''),
                                                    'contact_no' => $picData['contact_no'] ?? '',
                                                    'email' => $picData['email'] ?? '',
                                                    'status' => $picData['status'] ?? 'Available'
                                                ];
                                            }
                                        }

                                        // Now handle the primary contact update if needed
                                        if ($primaryContactSelected && $primaryContactIndex !== null) {
                                            $primaryPic = $prospectPics[$primaryContactIndex];

                                            // Get the current primary contact info before replacing it
                                            $currentPrimaryContact = null;
                                            if ($record->companyDetail) {
                                                $currentPrimaryContact = [
                                                    'name' => $record->companyDetail->name ?? '',
                                                    'position' => $record->companyDetail->position ?? '',
                                                    'email' => $record->companyDetail->email ?? '',
                                                    'contact_no' => $record->companyDetail->contact_no ?? '',
                                                    'status' => 'Available'  // Default status for the swapped contact
                                                ];
                                            }

                                            // Update the company details with this contact's info
                                            $record->companyDetail->update([
                                                'name' => $primaryPic['name'],
                                                'position' => $primaryPic['position'],
                                                'email' => $primaryPic['email'],
                                                'contact_no' => $primaryPic['contact_no'],
                                            ]);

                                            // Log the primary contact change
                                            activity()
                                                ->causedBy(auth()->user())
                                                ->performedOn($record)
                                                ->log('Updated primary contact person while updating contact status');

                                            // If the current primary contact has valid data, add it to the additional contacts
                                            if ($currentPrimaryContact && !empty($currentPrimaryContact['name'])) {
                                                // Make sure we're not adding an empty contact
                                                if (!empty($currentPrimaryContact['name']) || !empty($currentPrimaryContact['email']) ||
                                                    !empty($currentPrimaryContact['contact_no'])) {

                                                    // Check if this exact contact is already in the list to avoid duplicates
                                                    $isDuplicate = false;
                                                    foreach ($prospectPics as $pic) {
                                                        if ($pic['name'] === $currentPrimaryContact['name'] &&
                                                            $pic['email'] === $currentPrimaryContact['email'] &&
                                                            $pic['contact_no'] === $currentPrimaryContact['contact_no']) {
                                                            $isDuplicate = true;
                                                            break;
                                                        }
                                                    }

                                                    if (!$isDuplicate) {
                                                        // Add the former primary contact to the additional contacts list
                                                        $prospectPics[] = $currentPrimaryContact;
                                                    }
                                                }
                                            }

                                            // Remove the selected contact from additional contacts (it's now the primary)
                                            unset($prospectPics[$primaryContactIndex]);

                                            // Re-index the array to avoid gaps
                                            $prospectPics = array_values($prospectPics);
                                        }

                                        // Save the updated PICs
                                        $record->companyDetail->update([
                                            'additional_prospect_pic' => json_encode($prospectPics)
                                        ]);

                                        // Prepare notification message
                                        $message = 'Contact status updated successfully';
                                        if ($primaryContactSelected) {
                                            $message .= ' and primary contact updated';
                                        }

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Updated prospect contact status');

                                        Notification::make()
                                            ->title($message)
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Failed to update prospect contact status: ' . $e->getMessage(), [
                                            'lead_id' => $record->id,
                                            'exception' => $e
                                        ]);

                                        Notification::make()
                                            ->title('Error updating contact status')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                ]),
        ];
    }
}
