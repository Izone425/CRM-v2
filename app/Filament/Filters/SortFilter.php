<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class SortFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            Select::make('sort_by')
                ->live()
                ->default('seq_desc')
                ->options([
                    'latest_action' => 'Latest Action',
                    'seq_desc' => 'Sequence Descending',
                    'seq_asc' => 'Sequence Ascending',
                ])
        ]);
    }

    //Override
    public function applyToBaseQuery(Builder $query, array $data = []): Builder
    {
        $filterData = $this->getState();

        if (empty($filterData['sort_by'])) {
            return $query;
        }

        // Clear existing orders first
        $query->reorder();

        return match($filterData['sort_by']) {
            'latest_action' => $query->orderBy('updated_at', 'desc'),
            'seq_desc' => $query->orderBy('id', 'desc'),
            'seq_asc' => $query->orderBy('id', 'asc'),
            default => $query
        };
    }
}
