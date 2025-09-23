<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
// use App\Models\ImplementerNote;
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

class ARDetailsTabs
{
    protected static function canEditPICs($record): bool
    {
        $user = auth()->user();

        // Admin users (role_id = 3) can always edit
        if ($user->role_id == 3) {
            return true;
        }

        // Get the renewal record for this lead
        $renewal = Renewal::where('lead_id', $record->id)->first();

        // Check if the current user is the assigned admin_renewal
        if ($renewal && $renewal->admin_renewal === $user->name) {
            return true;
        }

        // Otherwise, no edit permissions
        return false;
    }

    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Section::make('New Renewal Details added by Admin Renewal')
                        // ->description('Add additional persons in charge for this lead')
                        ->icon('heroicon-o-users')
                        ->schema([
                            // Display saved new PICs in a card view
                            View::make('components.new-pic-cards')
                        ])
                        ->headerActions([
                            Action::make('add_new_pic')
                                ->label('Add New PIC Details')
                                ->icon('heroicon-o-user-plus')
                                ->color('primary')
                                ->modalWidth('5xl')
                                ->visible(function ($record) {
                                    // Only show this action if user has edit permissions
                                    return self::canEditPICs($record);
                                })
                                ->form([
                                    // Add the repeater directly in the section
                                    Repeater::make('additional_pic')
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255)
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
                                                        ->columnSpan(1),

                                                    TextInput::make('position')
                                                        ->maxLength(255)
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
                                ->visible(function ($record) {
                                    // First check if user has edit permissions
                                    if (!self::canEditPICs($record)) {
                                        return false;
                                    }

                                    // Check if record exists, has companyDetail, and has additional_pic
                                    if (!$record || !$record->companyDetail || empty($record->companyDetail->additional_pic)) {
                                        return false;
                                    }

                                    // Decode the PICs if needed
                                    $additionalPics = [];
                                    if (is_string($record->companyDetail->additional_pic)) {
                                        $additionalPics = json_decode($record->companyDetail->additional_pic, true) ?? [];
                                    } else {
                                        $additionalPics = $record->companyDetail->additional_pic ?? [];
                                    }

                                    // Only show button if there are PICs
                                    return count($additionalPics) > 0;
                                })
                                ->form(function ($record) {
                                    if (!$record || !$record->companyDetail || empty($record->companyDetail->additional_pic)) {
                                        Notification::make()
                                            ->title('No PICs Found')
                                            ->body('No original PICs found in the software handover.')
                                            ->warning()
                                            ->send();
                                        return;
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
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                                            ->disabled(fn() => !in_array(auth()->user()->role_id, [3, 5]))
                                                            ->columnSpan(1),

                                                        TextInput::make("new_pics.{$index}.position")
                                                            ->label('Position')
                                                            ->default($pic['position'] ?? '')
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                                            ->disabled(fn() => !in_array(auth()->user()->role_id, [3, 5]))
                                                            ->columnSpan(1),

                                                        TextInput::make("new_pics.{$index}.hp_number")
                                                            ->label('Phone Number')
                                                            ->default($pic['hp_number'] ?? '')
                                                            ->required()
                                                            ->tel()
                                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                            ->maxLength(20)
                                                            ->disabled(fn() => !in_array(auth()->user()->role_id, [3, 5]))
                                                            ->columnSpan(1),

                                                        TextInput::make("new_pics.{$index}.email")
                                                            ->label('Email')
                                                            ->default($pic['email'] ?? '')
                                                            ->required()
                                                            ->email()
                                                            ->maxLength(255)
                                                            ->disabled(fn() => !in_array(auth()->user()->role_id, [3, 5]))
                                                            ->columnSpan(1),

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
                                                // Restore the original data and update fields
                                                $originalData = json_decode($picData['original_data'], true);

                                                // If user has appropriate role, update all fields
                                                if (in_array(auth()->user()->role_id, [3, 5])) {
                                                    $originalData['name'] = $picData['name'] ?? $originalData['name'];
                                                    $originalData['position'] = $picData['position'] ?? $originalData['position'];
                                                    $originalData['hp_number'] = $picData['hp_number'] ?? $originalData['hp_number'];
                                                    $originalData['email'] = $picData['email'] ?? $originalData['email'];
                                                }

                                                // Always update status
                                                $originalData['status'] = $picData['status'];
                                                $additionalPics[$index] = $originalData;
                                            } else {
                                                // If original_data is missing, just update directly
                                                if (isset($additionalPics[$index])) {
                                                    $additionalPics[$index]['status'] = $picData['status'];

                                                    // If user has appropriate role, update other fields too
                                                    if (in_array(auth()->user()->role_id, [3, 5])) {
                                                        $additionalPics[$index]['name'] = $picData['name'] ?? $additionalPics[$index]['name'];
                                                        $additionalPics[$index]['position'] = $picData['position'] ?? $additionalPics[$index]['position'];
                                                        $additionalPics[$index]['hp_number'] = $picData['hp_number'] ?? $additionalPics[$index]['hp_number'];
                                                        $additionalPics[$index]['email'] = $picData['email'] ?? $additionalPics[$index]['email'];
                                                    }
                                                } else {
                                                    // Create minimal record if nothing exists at this index
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
