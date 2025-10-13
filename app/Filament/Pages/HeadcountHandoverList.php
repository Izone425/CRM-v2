<?php
namespace App\Filament\Pages;

use App\Models\HeadcountHandover;
use App\Models\CompanyDetail;
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

class HeadcountHandoverList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.headcount-handover-list';
    protected static ?string $slug = 'headcount-id';

    public function table(Table $table): Table
    {
        return $table
            ->query(HeadcountHandover::query())
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),
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
                    }),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'warning',
                        'InProgress' => 'info',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('Not completed'),

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not completed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Open' => 'Open',
                        'Pending' => 'Pending',
                        'InProgress' => 'In Progress',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                        'Rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Created By')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('completed_by')
                    ->label('Completed By')
                    ->relationship('completedBy', 'name')
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
