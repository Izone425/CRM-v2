<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Services\CategoryService;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Illuminate\View\View;
use Livewire\Attributes\On;

class SHTableRelationManager extends RelationManager
{
    protected static string $relationship = 'softwareHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    #[On('refresh-software-handovers')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting logic that uses the raw ID value
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('md')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        // This will control what's displayed
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        return $state;
                    })
                    ->url(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);
                            return url('admin/leads/' . $encryptedId);
                        }

                        return null; // No URL if no company found
                    })
                    ->openUrlInNewTab()
                    ->color(function ($record) {
                        $company = CompanyDetail::where('company_name', $record->company_name)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if (filled($company)) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),

                TextColumn::make('salesperson')
                    ->label('SalesPerson'),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status_handover')
                    ->label('Status')
                    ->toggleable()
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '')),

                TextColumn::make('ta')
                    ->label('TA')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(),

                TextColumn::make('tl')
                    ->label('TL')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(),

                TextColumn::make('tc')
                    ->label('TC')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(),

                TextColumn::make('tp')
                    ->label('TP')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(),

                TextColumn::make('tapp')
                    ->label('TAPP')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('thire')
                    ->label('THIRE')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tacc')
                    ->label('TACC')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tpbi')
                    ->label('TPBI')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payroll_code')
                    ->label('Payroll Code')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('company_size_label')
                    ->label('Company Size')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && isset($record->headcount)) {
                            $categoryService = app(CategoryService::class);
                            return $categoryService->retrieve($record->headcount);
                        }
                        return $state ?? 'N/A';
                    })
                    ->toggleable(),
                TextColumn::make('headcount')
                    ->label('Headcount')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('DB Creation')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        // Check if completed_at exists
                        if (!$record->go_live_date) {
                            try {
                                $completedDate = Carbon::parse($record->completed_at);
                                $today = Carbon::now();
                                // Calculate the difference in days
                                $daysDifference = $completedDate->diffInDays($today);

                                return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                            } catch (\Exception $e) {
                                return 'Error: ' . $e->getMessage();
                            }
                        }

                        try {
                           $goLiveDate = Carbon::parse($record->go_live_date);
                           $completedDate = Carbon::parse($record->completed_at);

                           $daysDifference = $completedDate->diffInDays($goLiveDate);

                           return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                        } catch (\Exception $e) {
                            // Return exception message for debugging
                            return 'Error: ' . $e->getMessage();
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('go_live_date')
                    ->label('Go Live Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kick_off_meeting')
                    ->label('Kick Off Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('webinar_training')
                    ->label('Webinar Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                ]);
    }
}
