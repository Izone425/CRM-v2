<?php

namespace App\Livewire;

use App\Enums\QuotationStatusEnum;
use App\Models\Company;
use App\Models\CompanyDetail;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ProformaInvoices extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    #[On('refresh-proforma-invoices')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function() {
                if(auth()->user()->role_id == 3){
                    return Quotation::where('status', QuotationStatusEnum::accepted)->orderBy('id', 'desc');
                    ;
                }else if(auth()->user()->role_id == 1){
                    return Quotation::where('status', QuotationStatusEnum::accepted)
                    ->whereHas('lead', function ($query) {
                        $query->where('lead_owner', auth()->user()->name)->orderBy('id', 'desc');
                    });
                }
                return Quotation::where('status', QuotationStatusEnum::accepted)->where('sales_person_id', auth()->user()->id)->orderBy('id', 'desc');
                ;
            })
            ->defaultPaginationPageOption(50)
            ->paginated([50, 100])
            ->paginationPageOptions([50, 100])
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
                    ->formatStateUsing(function ($state, Quotation $record) {
                        // Determine which company name to display
                        $companyName = 'N/A';
                        if ($record->subsidiary_id && $record->subsidiary) {
                            $companyName = $record->subsidiary->company_name;
                        } elseif ($record->lead && $record->lead->companyDetail) {
                            $companyName = $record->lead->companyDetail->company_name;
                        }

                        // Format the display name
                        $fullName = $companyName;
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),
                TextColumn::make('currency')
                    ->alignCenter(),
                TextColumn::make('items_sum_total_before_tax')
                    ->label('Value (Before Tax)')
                    ->sum('items','total_before_tax')
                    ->summarize([
                            \Filament\Tables\Columns\Summarizers\Summarizer::make()
                                ->label('Total:')
                                ->using(function ($query) {
                                    // Get all records from the current query
                                    $records = $query->get();
                                    $total = 0;

                                    // Calculate the sum manually
                                    foreach ($records as $record) {
                                        $total += $record->items_sum_total_before_tax ?? 0;
                                    }

                                    return number_format($total, 2);
                                }),
                        ])
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
                        TextInput::make('company_name')
                            ->label('Company')
                            ->placeholder('Enter company name'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['company_name'])) {
                            $query->whereHas('lead.companyDetail', function ($query) use ($data) {
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
                    ->label('Salesperson')
                    ->relationship('sales_person', 'name')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn(User $user, ?string $search, QuotationService $quotationService): array => $quotationService->searchSalesPersonName($user, $search)
                    )
                    ->getOptionLabelUsing(
                        fn(User $user, $value, QuotationService $quotationService): string => $quotationService->getSalesPersonName($user, $value)
                    )
                    ->hidden(function () {
                        $currentUser = auth()->user();

                        // Hide filter for role_id = 2 since they only see their own data
                        return $currentUser->role_id == 2;
                    }),
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
                ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice-v2', $quotation))
                ->openUrlInNewTab()
                ->hidden(fn(Quotation $quotation) => $quotation->status != QuotationStatusEnum::accepted),
            ]);
    }

    public function render()
    {
        return view('livewire.proforma-invoices');
    }
}
