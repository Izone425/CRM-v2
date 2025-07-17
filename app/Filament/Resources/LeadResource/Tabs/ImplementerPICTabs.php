<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\ImplementerNote;
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
                    Section::make('Additional Contacts')
                        ->description('Add additional persons in charge for this lead')
                        ->icon('heroicon-o-users')
                        ->schema([
                            // Display saved PICs in a card view
                            View::make('components.pic-cards')
                                ->visible(fn ($record) => $record && $record->companyDetail && !empty($record->companyDetail->additional_pic))
                        ])
                        ->headerActions([
                            Action::make('save_pic')
                                ->label('Save PICs')
                                // ->icon('heroicon-s-save')
                                ->color('primary')
                                ->form([
                                    // Add the repeater directly in the section
                                    Repeater::make('additional_pic')
                                        ->schema([
                                            Grid::make(4)
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->columnSpan(1),

                                                    TextInput::make('position')
                                                        ->maxLength(255)
                                                        ->columnSpan(1),

                                                    TextInput::make('hp_number')
                                                        ->required()
                                                        ->tel()
                                                        ->maxLength(20)
                                                        ->columnSpan(1),

                                                    TextInput::make('email')
                                                        ->required()
                                                        ->email()
                                                        ->maxLength(255)
                                                        ->columnSpan(1),
                                                ]),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                        ->collapsible()
                                        ->createItemButtonLabel('Add PIC')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            // Load existing additional PICs from companyDetails if they exist
                                            if ($record && $record->companyDetail && !empty($record->companyDetail->additional_pic)) {
                                                $additionalPics = json_decode($record->companyDetail->additional_pic, true);
                                                if (is_array($additionalPics)) {
                                                    $component->state($additionalPics);
                                                }
                                            }
                                        }),
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
                                        // Save to company details
                                        $record->companyDetail->update([
                                            'additional_pic' => json_encode($data['additional_pic'] ?? [])
                                        ]);

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Updated additional PICs for lead');

                                        Notification::make()
                                            ->title('Contacts saved successfully')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Failed to save PICs: ' . $e->getMessage(), [
                                            'lead_id' => $record->id,
                                            'exception' => $e
                                        ]);

                                        Notification::make()
                                            ->title('Error saving contacts')
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
