<?php
namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AdminPortalHrV2 extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin-portal-hr-v2';

    protected static ?string $navigationLabel = 'HR V2 Database Portal';

    protected static ?string $title = 'Admin Portal HR V2';

    protected static ?string $navigationGroup = 'Admin Portal';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SoftwareHandover::query()
                    ->where('hr_version', 2)
                    ->whereNotNull('hr_company_id')
                    ->orderBy('completed_at', 'desc')
            )
            ->columns([
                TextColumn::make('hr_company_id')
                    ->label(new HtmlString('Database<br>Backend ID'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Backend ID copied!')
                    ->weight('bold'),

                TextColumn::make('completed_at')
                    ->label(new HtmlString('Date & Time<br>DB Created'))
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(),

                TextColumn::make('project_code')
                    ->label('Software Handover ID')
                    ->copyable()
                    ->copyMessage('Handover ID copied!')
                    ->getStateUsing(fn (SoftwareHandover $record) => $record->project_code),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('master_email')
                    ->label('Master Email')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->email ?? "sw{$record->id}@timeteccloud.com";
                    })
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->searchable(),

                TextColumn::make('plain_password')
                    ->label('Master Password')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->plain_password ?? 'N/A';
                    })
                    ->copyable()
                    ->copyMessage('Password copied!'),

                // TextColumn::make('status')
                //     ->label('Status')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'New' => 'warning',
                //         'Approved' => 'info',
                //         'Completed' => 'success',
                //         'Rejected' => 'danger',
                //         default => 'gray',
                //     })
                //     ->sortable(),

                // TextColumn::make('hr_login_url')
                //     ->label('Master Login Access')
                //     ->getStateUsing(function (SoftwareHandover $record) {
                //         if ($record->hr_company_id) {
                //             return "https://hr.timeteccloud.com/company/{$record->hr_company_id}";
                //         }
                //         return null;
                //     })
                //     ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                //     ->icon('heroicon-o-arrow-top-right-on-square')
                //     ->color('primary')
                //     ->placeholder('N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Completed' => 'Completed',
                        'Rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Action::make('view_audit_trail')
                //     ->label('Audit Trail')
                //     ->icon('heroicon-o-document-text')
                //     ->color('info')
                //     ->url(fn (SoftwareHandover $record): string =>
                //         route('filament.admin.pages.hr-v2-audit-trail', ['handover' => $record->id])
                //     )
                //     ->openUrlInNewTab(),

                // Action::make('update_credentials')
                //     ->label('Update Credentials')
                //     ->icon('heroicon-o-key')
                //     ->form([
                //         TextInput::make('master_email')
                //             ->label('Master Email')
                //             ->email()
                //             ->required(),
                //         TextInput::make('master_password')
                //             ->label('Master Password')
                //             ->password()
                //             ->revealable()
                //             ->required(),
                //     ])
                //     ->fillForm(function (SoftwareHandover $record) {
                //         $customer = Customer::where('sw_id', $record->id)->first();
                //         return [
                //             'master_email' => $customer?->master_email ?? "sw{$record->id}@timeteccloud.com",
                //             'master_password' => $customer?->master_password ?? '',
                //         ];
                //     })
                //     ->action(function (SoftwareHandover $record, array $data) {
                //         $customer = Customer::firstOrCreate(
                //             ['sw_id' => $record->id],
                //             [
                //                 'master_email' => $data['master_email'],
                //                 'master_password' => $data['master_password'],
                //             ]
                //         );

                //         $customer->update([
                //             'master_email' => $data['master_email'],
                //             'master_password' => $data['master_password'],
                //         ]);

                //         Notification::make()
                //             ->title('Credentials Updated')
                //             ->success()
                //             ->send();
                //     }),
            ])
            ->bulkActions([
                // Add bulk actions if needed
            ])
            ->defaultSort('completed_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        // Add your authorization logic here
        return auth()->check();
    }
}
