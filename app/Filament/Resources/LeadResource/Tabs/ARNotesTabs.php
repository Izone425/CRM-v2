<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\ImplementerNote;
use App\Models\Renewal;
use App\Models\RenewalNote;
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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class ARNotesTabs
{
    protected static function canEditNotes($record): bool
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
                    Section::make('Renewal Notes')
                        ->icon('heroicon-o-document-text')
                        ->headerActions([
                            Action::make('add_note')
                                ->label('Add Note')
                                ->button()
                                ->color('primary')
                                ->icon('heroicon-o-plus')
                                ->visible(function ($record) {
                                    return self::canEditNotes($record);
                                })
                                ->form([
                                    RichEditor::make('notes')
                                        ->label('New Note')
                                        ->disableToolbarButtons([
                                            'attachFiles',
                                            'blockquote',
                                            'codeBlock',
                                            'h2',
                                            'h3',
                                            'link',
                                            'redo',
                                            'strike',
                                            'undo',
                                        ])
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->placeholder('Add your note here...')
                                        ->required()
                                ])
                                ->modalHeading('Add New Note')
                                ->modalWidth('3xl')
                                ->action(function (Lead $record, array $data) {
                                    // Create a new renewal note
                                    RenewalNote::create([
                                        'lead_id' => $record->id,
                                        'user_id' => auth()->id(),
                                        'content' => $data['notes'],
                                    ]);

                                    Notification::make()
                                        ->title('Note added successfully')
                                        ->success()
                                        ->send();
                                }),
                        ])
                        ->schema([
                            Card::make()
                                ->schema([
                                    View::make('components.renewal-note-history')
                                        ->extraAttributes(['class' => 'p-0']),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
