<?php
namespace App\Filament\Pages;

use App\Models\HeadcountHandover;
use App\Models\CompanyDetail;
use App\Models\HRDFHandover;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;

class HrdfHandoverList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'HRDF Handover List';
    protected static string $view = 'filament.pages.hrdf-handover-list';
    protected static ?string $slug = 'hrdf-id';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HRDFHandover::query()
                    ->with(['lead.companyDetail', 'creator', 'completedBy'])
            )
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('HRDF ID')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HRDFHandover $record): View {
                                return view('components.hrdf-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    )
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);
                            return url('admin/leads/' . $encryptedId);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return strtoupper(Str::limit($record->lead->companyDetail->company_name, 30, '...'));
                        }

                        return $record->company_name ? strtoupper(Str::limit($record->company_name, 30, '...')) : '-';
                    })
                    ->color(function ($record) {
                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }
                        return Color::hex("#000000");
                    })
                    ->sortable(),

                TextColumn::make('lead.salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(function (HRDFHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'warning',
                        'Pending' => 'warning',
                        'InProgress' => 'info',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('hrdf_claim_id')
                    ->label('HRDF Claim ID')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('autocount_invoice_number')
                    ->label('AutoCount Inv No')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Completed' => 'Completed',
                        'Rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('salesperson')
                    ->label('SalesPerson')
                    ->options(function () {
                        // Get all users who are salespersons in leads table
                        return User::whereIn('id', function($query) {
                            $query->select('salesperson')
                                ->from('leads')
                                ->whereNotNull('salesperson')
                                ->distinct();
                        })
                        ->pluck('name', 'id')
                        ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('lead', function ($leadQuery) use ($data) {
                                $leadQuery->where('salesperson', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker::make('date_range')
                            ->label('Created Date Range')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            return 'Created: ' . \Carbon\Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' - ' . \Carbon\Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),
            ])
            ->defaultSort('id', 'desc');
    }
}
