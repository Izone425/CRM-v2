<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Services\CategoryService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\HtmlString;

class ProjectCategoryDelay extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Project Category Delay';
    protected static ?string $title = 'Project Category Delay';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.project-category-delay';

    public function table(Table $table): Table
    {
        return $table
            ->query(SoftwareHandover::query()->where('status_handover', 'Delay'))
            ->defaultPaginationPageOption(50)
            ->defaultSort('id', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    }),

                TextColumn::make('company_name')
                    ->label('COMPANY NAME')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
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

                        return null;
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

                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->sortable(),

                TextColumn::make('status_handover')
                    ->label('PROJECT STATUS')
                    ->sortable()
                    ->formatStateUsing(fn($state) => strtoupper($state ?? 'Open')),

                // Module columns grouped together for better readability
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

                TextColumn::make('company_size_label')
                    ->label('COMPANY SIZE')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && isset($record->headcount)) {
                            $categoryService = app(CategoryService::class);
                            return $categoryService->retrieve($record->headcount);
                        }
                        return $state ?? 'N/A';
                    })
                    ->toggleable(),

                TextColumn::make('headcount')
                    ->label('HEADCOUNT')
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('DB CREATION')
                    ->date('d M Y')
                    ->toggleable(),

                TextColumn::make('total_days')
                    ->label('TOTAL DAYS')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        if (!$record->go_live_date) {
                            try {
                                $completedDate = Carbon::parse($record->completed_at);
                                $today = Carbon::now();
                                $daysDifference = $completedDate->diffInDays($today);
                                return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                            } catch (\Exception $e) {
                                return 'N/A';
                            }
                        }

                        try {
                            $goLiveDate = Carbon::parse($record->go_live_date);
                            $completedDate = Carbon::parse($record->completed_at);
                            $daysDifference = $completedDate->diffInDays($goLiveDate);
                            return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                        } catch (\Exception $e) {
                            return 'N/A';
                        }
                    }),

                TextColumn::make('go_live_date')
                    ->label('GO LIVE DATE')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kick_off_meeting')
                    ->label('KICK OFF DATE')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('webinar_training')
                    ->label('WEBINAR TRAINING DATE')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hrdf_training_date')
                    ->label('HRDF TRAINING DATE')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return \App\Models\User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    }),
                SelectFilter::make('module_configuration')
                    ->label('Filter by Module Configuration')
                    ->options([
                        'full_module' => 'Full Module (TA+TL+TC+TP)',
                        'non_full_module' => 'Non-Full Module',
                        'non_payroll' => 'Non-Payroll (No TP)',
                        'ta_only' => 'TA Only',
                        'tl_only' => 'TL Only',
                        'tc_only' => 'TC Only',
                        'tp_only' => 'TP Only',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'full_module' => $query->where('ta', true)
                                ->where('tl', true)
                                ->where('tc', true)
                                ->where('tp', true),
                            'non_full_module' => $query->where(function ($q) {
                                $q->where('ta', false)
                                    ->orWhere('tl', false)
                                    ->orWhere('tc', false)
                                    ->orWhere('tp', false);
                            }),
                            'non_payroll' => $query->where('tp', false),
                            'ta_only' => $query->where('ta', true)
                                ->where('tl', false)
                                ->where('tc', false)
                                ->where('tp', false),
                            'tl_only' => $query->where('ta', false)
                                ->where('tl', true)
                                ->where('tc', false)
                                ->where('tp', false),
                            'tc_only' => $query->where('ta', false)
                                ->where('tl', false)
                                ->where('tc', true)
                                ->where('tp', false),
                            'tp_only' => $query->where('ta', false)
                                ->where('tl', false)
                                ->where('tc', false)
                                ->where('tp', true),
                            default => $query,
                        };
                    }),
                SelectFilter::make('company_size')
                    ->label('Filter by Company Size')
                    ->options([
                        'Small' => 'Small (1-24)',
                        'Medium' => 'Medium (25-99)',
                        'Large' => 'Large (100-500)',
                        'Enterprise' => 'Enterprise (501+)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'Small' => $query->whereBetween('headcount', [1, 24]),
                            'Medium' => $query->whereBetween('headcount', [25, 99]),
                            'Large' => $query->whereBetween('headcount', [100, 500]),
                            'Enterprise' => $query->where('headcount', '>=', 501),
                            default => $query,
                        };
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Check if user is regular implementer (role 4) and not in the special list
                if ($user && $user->role_id === 4) {
                    // Regular implementers can only see their own projects
                    $query->where('implementer', $user->name);
                }
            });
    }
}
