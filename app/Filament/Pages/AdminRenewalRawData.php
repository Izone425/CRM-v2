<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// Create a temporary model for the query
class RenewalData extends Model
{
    // Set the connection to the frontenddb database
    protected $connection = 'frontenddb';
    protected $table = 'company';
    protected $primaryKey = 'f_id';
    public $timestamps = false;

    // Define the base query for this model
    public function scopeRenewalQuery($query)
    {
        return $query
            ->from('company as d')
            ->join('company_license as a', function ($join) {
                $join->on('d.f_id', '=', 'a.f_company_id')
                    ->where('a.f_unit', '>', 0)
                    ->where('a.f_expiry_date', '>=', '2025-01-01 00:00:00')
                    ->where('a.f_type', '=', 'PAID');
            })
            ->join('ac_invoice as b', 'a.f_invoice_id', '=', 'b.f_id')
            ->join('product as c', 'a.f_product_id', '=', 'c.f_id')
            ->join('company as payer', 'b.f_payer_id', '=', 'payer.f_id')
            ->leftJoin('company_user as created', 'b.f_created_by', '=', 'created.f_id')
            ->select([
                'b.f_currency',
                'b.f_id',
                'd.f_company_name',
                'd.f_id as f_company_id',
                'c.f_name',
                'b.f_invoice_no',
                'b.f_total_amount',
                'a.f_unit',
                'a.f_start_date',
                'a.f_expiry_date',
                'created.f_fullname as Created',
                'payer.f_company_name as payer',
                'payer.f_id as payer_id',
                'b.f_created_time'
            ]);
    }
}

class AdminRenewalRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Renewal Raw Data';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.admin-renewal-raw-data';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // This returns an Eloquent Builder, which is what Filament expects
                RenewalData::renewalQuery()
            )
            ->columns([
                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('f_company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_total_amount')
                    ->label('Total Amount')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('f_unit')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('f_start_date')
                    ->label('Start Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('f_expiry_date')
                    ->label('Expiry Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('Created')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payer')
                    ->label('Payer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_created_time')
                    ->label('Created Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('f_expiry_date', 'asc');
    }
}
