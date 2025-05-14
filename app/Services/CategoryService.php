<?php

namespace App\Services;

use Filament\Forms;

class CategoryService
{
    public function retrieve(?string $state): string
    {
        $categoryValue = '';

        if ($state) {
            $value = strval($state);

            if ($value > 0 && $value < 25) {
                $categoryValue = 'SMALL';
            }
            if ($value >= 25 && $value < 100) {
                $categoryValue = 'MEDIUM';
            }
            if ($value >= 100 && $value < 500) {
                $categoryValue = 'LARGE';
            }
            if ($value >= 500) {
                $categoryValue = 'ENTERPRISE';
            }
        }

        return $categoryValue;
    }
}
