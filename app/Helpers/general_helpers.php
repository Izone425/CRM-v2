<?php

use Illuminate\Support\Str;

if (!function_exists('generate_company_id')) {
    function generate_company_id(int $id): string
    {
        return sprintf('HR%06d', $id);
    }
}

if (!function_exists('quotation_reference_no')) {
    function quotation_reference_no(int $num): string
    {
        $maxNumber = 9999;
        $startingNumber = 0;
        $referenceNo = $startingNumber + $num;

        $year = now()->format('y');

        return $referenceNo%$maxNumber == 0 ? $year . (string) $maxNumber : $year . (string) sprintf("%04d", $referenceNo%$maxNumber);
    }
}

if (!function_exists('remove_company_suffix')) {
    function remove_company_suffix($company_name)
    {
        if (Str::endsWith($company_name, 'Sdn. Bhd.'))
        {
           return Str::before($company_name, ' Sdn. Bhd.');
        }

        if (Str::endsWith($company_name, 'Sdn Bhd'))
        {
           return Str::before($company_name, ' Sdn Bhd');
        }

        if (Str::endsWith($company_name, 'Bhd.'))
        {
           return Str::before($company_name, ' Bhd.');
        }

        if (Str::endsWith($company_name, 'Berhad'))
        {
           return Str::before($company_name, ' Berhad');
        }

        if (Str::endsWith($company_name, 'Ltd'))
        {
           return Str::before($company_name, ' Ltd');
        }

        if (Str::endsWith($company_name, 'Ltd.'))
        {
           return Str::before($company_name, ' Ltd.');
        }

        if (Str::endsWith($company_name, 'Pte Ltd'))
        {
           return Str::before($company_name, ' Pte Ltd');
        }

        if (Str::endsWith($company_name, 'Pte. Ltd.'))
        {
           return Str::before($company_name, ' Pte. Ltd.');
        }

        if (Str::endsWith($company_name, 'Limited'))
        {
           return Str::before($company_name, ' Limited');
        }

        if (Str::endsWith($company_name, 'Company'))
        {
           return Str::before($company_name, ' Company');
        }

        if (Str::startsWith($company_name, 'Syarikat'))
        {
            return Str::after($company_name, 'Syarikat ');
        }
    }
}
