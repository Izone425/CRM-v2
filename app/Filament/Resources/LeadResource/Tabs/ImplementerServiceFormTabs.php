<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\ImplementerForm;
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
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class ImplementerServiceFormTabs
{
    protected static ?int $indexDeviceCounter = 0;

    public static function getSchema(): array
    {
        self::$indexDeviceCounter = 0;

        return [
            Grid::make(1)
                ->schema([
                    Section::make('Service Forms')
                        ->description('Manage service forms for this lead')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            // Display existing service forms
                            View::make('components.service-forms-list')
                                ->visible(fn ($record) => $record && $record->implementerForms && $record->implementerForms->count() > 0)
                        ])
                        ->headerActions([
                            Action::make('submit_service_form')
                                ->label('Submit Service Form')
                                ->color('primary')
                                ->icon('heroicon-o-paper-airplane')
                                ->form([
                                    Card::make()
                                        ->schema([
                                            Repeater::make('service_forms')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('remarks')
                                                            ->label('Service Form Email')
                                                            ->placeholder('ENTER REMARKS HERE')
                                                            ->rows(3)
                                                            ->columnSpan(1),

                                                        FileUpload::make('attachment')
                                                            ->label('Service Form Attachment')
                                                            ->directory('service-forms')
                                                            ->acceptedFileTypes([
                                                                'application/pdf',
                                                                'image/*',
                                                                'application/msword',
                                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                                            ])
                                                            ->helperText('Drag & Drop your files or Browse')
                                                            ->columnSpan(1),
                                                    ]),
                                            ])
                                            ->itemLabel(fn() => __('Service Form') . ' ' . ++self::$indexDeviceCounter)
                                            ->hiddenLabel()
                                            ->collapsible()
                                            ->defaultItems(1)
                                            ->minItems(1)
                                            ->addActionLabel('Add Service Form')
                                            ->reorderable(false)
                                            ->columns(1)
                                        ])
                                        ->columnSpan(1),
                                ])
                                ->action(function (Lead $record, array $data) {
                                    if (!$record) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Lead record not found')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        // Process each service form in the repeater
                                        foreach ($data['service_forms'] as $formData) {
                                            // Create implementer form record
                                            $implementerForm = new ImplementerForm();
                                            $implementerForm->lead_id = $record->id;
                                            $implementerForm->filepath = $formData['attachment'] ?? null;
                                            $implementerForm->notes = $formData['remarks'] ?? null;
                                            $implementerForm->save();
                                        }

                                        // Log activity
                                        activity()
                                            ->causedBy(auth()->user())
                                            ->performedOn($record)
                                            ->log('Added new service form(s)');

                                        Notification::make()
                                            ->title('Service form(s) submitted successfully')
                                            ->success()
                                            ->send();

                                    } catch (\Exception $e) {
                                        Log::error('Failed to submit service form: ' . $e->getMessage(), [
                                            'lead_id' => $record->id,
                                            'exception' => $e
                                        ]);

                                        Notification::make()
                                            ->title('Error submitting service form')
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
