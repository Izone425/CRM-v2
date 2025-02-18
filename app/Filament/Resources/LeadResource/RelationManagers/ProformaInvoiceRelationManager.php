<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table as TablesTable;
use App\Enums\QuotationStatusEnum;
use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Setting;
use App\Services\QuotationService;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProformaInvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations'; // Define the relationship name in the Lead model
    protected $quotation;

    use InteractsWithTable;
    use InteractsWithForms;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->query(function () {
                $leadId = $this->getOwnerRecord()->id; // Get the lead ID dynamically
                // if (in_array(auth()->user()->role, ['admin', 'manager'])) {
                //     return Quotation::where('status', QuotationStatusEnum::accepted)
                //                     ->where('lead_id', $leadId);
                // }

                $this->quotation = Quotation::where('lead_id', $leadId)->latest()->first();

                return Quotation::where('status', QuotationStatusEnum::accepted)
                                ->where('lead_id', $leadId);
            })
            ->columns([
                TextColumn::make('pi_reference_no')
                    ->label('Ref No'),
                TextColumn::make('quotation_date')
                    ->label('Date')
                    ->formatStateUsing(fn($state) => $state->format('Y-m-d')),
                TextColumn::make('quotation_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match($state) {
                        'product' => 'Product',
                        'hrdf' => 'HRDF',
                    }),
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company')
                    ->formatStateUsing(fn($state): string => Str::upper($state)),
                TextColumn::make('currency')
                    ->alignCenter(),
                TextColumn::make('items_sum_total_after_tax')
                    ->label('Value')
                    ->sum('items','total_after_tax')
                    // ->summarize([
                    //     Sum::make()
                    //         ->label('Total')
                    //         ->formatStateUsing(fn($state) => number_format($state,2,'.','')),
                    // ])
                    //->formatStateUsing(fn(Model $record, $state) => $record->currency . ' ' . $state)
                    ->alignRight(),
                TextColumn::make('sales_person.name')
                    ->label('Sales Person'),
            ])
            ->filters([
                SelectFilter::make('pi_reference_no')
                    ->label('Ref No')
                    ->searchable()
                    ->getSearchResultsUsing(fn(Quotation $quotation, ?string $search, QuotationService $quotationService): array => $quotationService->searchProformaInvoiceByReferenceNo($quotation, $search))
                    ->getOptionLabelsUsing(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getProformaInvoiceList($quotation)),
                // Filter::make('quotation_reference_no')
                //     ->form([
                //         Select::make('quotation_reference_no')
                //             ->label('Ref No')
                //             ->placeholder('Search by ref no')
                //             ->options(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getQuotationList($quotation))
                //             ->searchable(),
                //     ])
                //     ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByReferenceNo($query, $data)),
                Filter::make('quotation_date')
                    ->label('Date')
                    ->form([
                        Flatpickr::make('quotation_date')
                            ->label('Date')
                            ->dateFormat('j M Y')
                            ->allowInput()
                    ])
                    ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByDate($query, $data)),
                SelectFilter::make('quotation_type')
                    ->label('Type')
                    ->searchable()
                    ->options([
                        'product' => 'Product',
                        'hrdf' => 'HRDF',
                        // 'other' => 'Others'
                    ]),
                Filter::make('company_name')
                    ->form([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company')
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['company_name'])) {
                            $query->whereHas('companyDetail', function ($query) use ($data) {
                                $query->where('company_name', 'like', '%' . $data['company_name'] . '%');
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return isset($data['company_name'])
                            ? 'Company Name: ' . $data['company_name']
                            : null;
                    }),
                SelectFilter::make('sales_person_id')
                    ->label('Sales Person')
                    ->relationship('sales_person', 'name')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn(User $user, ?string $search, QuotationService $quotationService): array => $quotationService->searchSalesPersonName($user, $search)
                    )
                    ->getOptionLabelUsing(
                        fn(User $user, $value, QuotationService $quotationService): string => $quotationService->getSalesPersonName($user, $value)
                    ),
                // SelectFilter::make('status')
                //     ->label('Status')
                //     ->searchable()
                //     ->options([
                //         'new' => 'New',
                //         'email_sent' => 'Email Sent',
                //         'accepted' => 'Accepted',
                //         // 'rejected' => 'Rejected',
                //     ])
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->actions([
                Tables\Actions\Action::make('proforma_invoice')
                ->label('View Proforma Invoice')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice', $quotation))
                ->openUrlInNewTab()
                ->hidden(fn(Quotation $quotation) => $quotation->status != QuotationStatusEnum::accepted),
            ]);
    }

    // public function render()
    // {
    //     return view('livewire.proforma-invoices');
    // }
}
