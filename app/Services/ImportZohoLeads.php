<?php

namespace App\Services;

use App\Imports\ContactImport;
use App\Imports\DealImport;
use App\Imports\LeadImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportZohoLeads
{
    public static function importLeads()
    {
        $file = public_path('storage/excel/Deals_2025_03_26.csv');
        $import = new LeadImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importContacts()
    {
        $file = public_path('storage/excel/Contacts_2025_03_07.csv');
        $import = new ContactImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importDeals()
    {
        $file = public_path('storage/excel/Deals_2025_03_07.csv');
        $import = new DealImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }
}
