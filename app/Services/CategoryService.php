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
                $categoryValue = 'Small';
            }
            if ($value >= 25 && $value < 100) {
                $categoryValue = 'Medium';
            }
            if ($value >= 100 && $value < 500) {
                $categoryValue = 'Large';
            }
            if ($value >= 500) {
                $categoryValue = 'Enterprise';
            }
        }

        return $categoryValue;
    }
}
