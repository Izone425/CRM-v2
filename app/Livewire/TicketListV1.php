<?php
namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketLog;
use App\Models\TicketPriority;
use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class TicketListV1 extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;
    use WithFileUploads;

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';
    public $attachments = [];

    protected $listeners = ['ticket-status-updated' => '$refresh'];

    public function render()
    {
        return view('livewire.ticket-list-v1');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::where('product_id', 1))
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('requestor.name')
                    ->label('Requestor')
                    ->searchable()
                    ->sortable()
                    ->default('Unknown User')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unknown User'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('priority.name')
                    ->label('Priority')
                    ->colors([
                        'danger' => fn ($state) => str_contains(strtolower($state ?? ''), 'bug') || str_contains(strtolower($state ?? ''), 'software'),
                        'warning' => fn ($state) => str_contains(strtolower($state ?? ''), 'backend') || str_contains(strtolower($state ?? ''), 'assistance'),
                        'primary' => fn ($state) => str_contains(strtolower($state ?? ''), 'critical enhancement'),
                        'info' => fn ($state) => str_contains(strtolower($state ?? ''), 'paid') || str_contains(strtolower($state ?? ''), 'customization'),
                        'success' => fn ($state) => str_contains(strtolower($state ?? ''), 'non-critical'),
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'New',
                        'warning' => 'In Progress',
                        'success' => 'Resolved',
                        'danger' => 'Closed',
                    ]),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn ($state) => $state === 'Mobile' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('zoho_id')
                    ->label('Zoho Ticket'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_modules')
                            ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
                            ->where('product_has_modules.product_id', 1)
                            ->where('modules.is_active', true)
                            ->orderBy('modules.name')
                            ->pluck('modules.name', 'modules.id')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->options(
                        TicketPriority::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    ),
            ])
            // ->actions([
            //     Tables\Actions\ActionGroup::make([
            //         Tables\Actions\Action::make('view')
            //             ->label('View')
            //             ->hidden()
            //             ->icon('heroicon-o-eye')
            //             ->action(fn (Ticket $record) => $this->viewTicket($record->id)),
            //     ])
            // ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
            // ✅ Removed headerActions - no create button
    }

    public function view($recordId): void
    {
        $this->viewTicket($recordId);
    }

    // ✅ Removed getActions() method - no create ticket form

    public function viewTicket($ticketId): void
    {
        try {
            $this->selectedTicket = Ticket::with([
                'comments',
                'logs',
                'priority',
                'product',
                'module',
                'requestor',
                'attachments',
                'attachments.uploader',
            ])->find($ticketId);

            if ($this->selectedTicket) {
                $this->showTicketModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Error viewing ticket: ' . $e->getMessage());
            $this->showTicketModal = false;
        }
    }

    public function closeTicketModal(): void
    {
        $this->showTicketModal = false;
        $this->selectedTicket = null;
        $this->newComment = '';
        $this->attachments = [];
    }

    public function addComment(): void
    {
        if (empty($this->newComment) || !$this->selectedTicket) {
            return;
        }

        try {
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('name', $authUser->name)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;

            TicketComment::create([
                'ticket_id' => $this->selectedTicket->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
            ]);

            $this->newComment = '';

            $this->selectedTicket->refresh();
            $this->selectedTicket->load('comments');

            Notification::make()
                ->title('Comment Added')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add comment')
                ->send();
        }
    }

    public function uploadAttachments(): void
    {
        $this->validate([
            'attachments.*' => 'file|max:10240',
        ]);

        if (empty($this->attachments) || !$this->selectedTicket) {
            Notification::make()
                ->title('No files selected')
                ->warning()
                ->send();
            return;
        }

        try {
            $authUser = auth()->user();
            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('users')
                ->where('name', $authUser->name)
                ->first();

            $userId = $ticketSystemUser?->id ?? 22;

            foreach ($this->attachments as $file) {
                $originalFilename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                '.' . $extension;

                $path = $file->storeAs(
                    'ticket_attachments/' . date('Y/m/d'),
                    $storedFilename,
                    's3-ticketing'
                );

                $fileHash = hash_file('md5', $file->getRealPath());

                TicketAttachment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'file_hash' => $fileHash,
                    'uploaded_by' => $userId,
                ]);
            }

            $this->attachments = [];
            $this->selectedTicket->refresh();
            $this->selectedTicket->load('attachments');

            Notification::make()
                ->title('Files Uploaded')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error uploading attachments: ' . $e->getMessage());

            Notification::make()
                ->title('Upload Failed')
                ->danger()
                ->body('Failed to upload files: ' . $e->getMessage())
                ->send();
        }
    }

    private function isImageFile($attachment): bool
    {
        if (str_starts_with($attachment->mime_type, 'image/')) {
            return true;
        }
        $extension = strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    public function updateTicketStatus($ticketId, string $newStatus): void
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('name', $authUser->name)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;
            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->update(['status' => $newStatus]);

            // ✅ Create comprehensive ticket log entry
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'action' => "Changed status from '{$oldStatus}' to '{$newStatus}' for ticket {$ticket->ticket_id}.",
                'field_name' => 'status',
                'change_reason' => null,
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                'change_type' => 'status_change',
                'source' => 'modal',
            ]);

            // ✅ Refresh the selected ticket with fresh data including logs
            $this->selectedTicket = $ticket->fresh(['logs', 'comments', 'attachments', 'priority', 'product', 'module', 'requestor']);

            Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("Ticket {$ticket->ticket_id} status changed from {$oldStatus} to {$newStatus}")
                ->send();

            // ✅ Dispatch event to refresh both V1 and V2 tables
            $this->dispatch('ticket-status-updated');

        } catch (\Exception $e) {
            Log::error('Error updating ticket status: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to update ticket status: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getFormSchema(): array
    {
        return [
            RichEditor::make('newComment')
                ->label('')
                ->placeholder('Add a comment...')
                ->required()
                ->toolbarButtons([
                    'attachFiles',
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'bulletList',
                    'orderedList',
                    'h2',
                    'h3',
                    'link',
                    'undo',
                    'redo',
                ])
                ->disableToolbarButtons([
                    'codeBlock',
                ])
        ];
    }
}
