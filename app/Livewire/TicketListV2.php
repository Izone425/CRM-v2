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
use Illuminate\Support\Facades\Storage;

class TicketListV2 extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;
    use WithFileUploads;

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';
    public $attachments = [];

    // Reopen modal properties
    public $showReopenModal = false;
    public $reopenComment = '';
    public $reopenAttachments = [];

    protected $listeners = ['ticket-status-updated' => '$refresh'];

    public function render()
    {
        return view('livewire.ticket-list-v2');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::where('product_id', 2)) // V2 only
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
                            ->where('product_has_modules.product_id', 2) // V2 modules only
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
        $this->closeReopenModal();
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
                    ->where('email', $authUser->email)
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

    // Reopen modal methods
    public function openReopenModal($ticketId = null)
    {
        if ($ticketId) {
            $this->selectedTicket = Ticket::with(['comments.user', 'attachments', 'client', 'priority', 'category', 'subcategory', 'product', 'module'])
                ->find($ticketId);
        }

        // Reset form data
        $this->reopenComment = '';
        $this->reopenAttachments = [];

        $this->showReopenModal = true;
    }

    public function closeReopenModal()
    {
        $this->showReopenModal = false;
        $this->reopenComment = '';
        $this->reopenAttachments = [];
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
                    ->where('email', $authUser->email)
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

    public function reopenTicket()
    {
        try {
            Log::info('Starting reopenTicket method in TicketListV2');

            // Get the current selected ticket
            $ticket = Ticket::find($this->selectedTicket->id);
            if (!$ticket) {
                throw new \Exception('Ticket not found');
            }

            // Get proper user ID for the ticketing system
            $authUser = auth()->user();
            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }
            $userId = $ticketSystemUser?->id ?? 22;

            // Handle file uploads first to get URLs for HTML comment
            $uploadedImageUrls = [];
            if (!empty($this->reopenAttachments)) {
                foreach ($this->reopenAttachments as $file) {
                    try {
                        if ($file && $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            // Generate unique filename
                            $originalFilename = $file->getClientOriginalName();
                            $extension = $file->getClientOriginalExtension();

                            $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                            \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                            '.' . $extension;

                            // Store file in S3
                            $path = $file->storeAs(
                                'ticket_attachments/' . date('Y/m/d'),
                                $storedFilename,
                                's3-ticketing'
                            );

                            $fileHash = hash_file('md5', $file->getRealPath());

                            // Create attachment record with correct field names
                            $attachment = TicketAttachment::create([
                                'ticket_id' => $ticket->id,
                                'original_filename' => $originalFilename,
                                'stored_filename' => $storedFilename,
                                'file_path' => $path,
                                'file_size' => $file->getSize(),
                                'mime_type' => $file->getMimeType(),
                                'file_hash' => $fileHash,
                                'uploaded_by' => $userId,
                            ]);

                            // If it's an image, collect the URL for HTML comment
                            if (str_starts_with($file->getMimeType(), 'image/')) {
                                $disk = Storage::disk('s3-ticketing');
                                $fileUrl = $disk->url($path);
                                $uploadedImageUrls[] = [
                                    'url' => $fileUrl,
                                    'filename' => $originalFilename
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error uploading reopen attachment: ' . $e->getMessage());
                    }
                }
            }

            // Create HTML comment combining text and images
            $htmlComment = '';
            if (!empty(trim($this->reopenComment))) {
                $htmlComment .= '<p>' . nl2br(e(trim($this->reopenComment))) . '</p>';
            }

            // Add image attachments as <p><img> tags
            foreach ($uploadedImageUrls as $image) {
                $htmlComment .= '<p><img src="' . $image['url'] . '" alt="' . e($image['filename']) . '" style="max-width: 100%; height: auto;" /></p>';
            }

            // Update ticket status and reopen reason
            $ticket->status = 'Reopen';
            if (!empty($htmlComment)) {
                $ticket->reopen_reason = $htmlComment;
            } elseif (!empty(trim($this->reopenComment))) {
                $ticket->reopen_reason = trim($this->reopenComment);
            }
            $ticket->save();

            // Also create a TicketComment for the reopen reason with HTML including images
            if (!empty($htmlComment)) {
                TicketComment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'user_id' => $userId,
                    'comment' => $htmlComment,
                ]);
            } elseif (!empty(trim($this->reopenComment))) {
                TicketComment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'user_id' => $userId,
                    'comment' => trim($this->reopenComment),
                ]);
            }

            // Log the action
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $this->selectedTicket->status ?? 'Closed',
                'new_value' => 'Reopen',
                'action' => "Reopened ticket {$ticket->ticket_id} from '{$this->selectedTicket->status}' to 'Reopen'.",
                'field_name' => 'status',
                'change_reason' => !empty($htmlComment) ? $htmlComment : (!empty(trim($this->reopenComment)) ? trim($this->reopenComment) : 'Ticket reopened without comment'),
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                'change_type' => 'status_change',
                'source' => 'reopen_modal',
            ]);

            // Close modal and reset form
            $this->closeReopenModal();

            // Update selected ticket data
            $this->selectedTicket->status = 'Reopen';

            // Dispatch events to refresh tables
            $this->dispatch('ticket-status-updated');
            $this->dispatch('close-reopen-modal');

            Notification::make()
                ->title('Success')
                ->success()
                ->body('Ticket has been successfully reopened')
                ->send();

        } catch (\Exception $e) {
            Log::error('Error reopening ticket: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to reopen ticket: ' . $e->getMessage())
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
