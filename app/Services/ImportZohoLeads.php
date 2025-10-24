<?php

namespace App\Services;

use App\Imports\ApolloImport;
use App\Imports\ContactImport;
use App\Imports\DealImport;
use App\Imports\HrdfClaimImport;
use App\Imports\LeadImport;
use App\Imports\SoftwareHandoverImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportZohoLeads
{
    public static function importLeads()
    {
        $file = public_path('storage/excel/Lead_2025_08_04.csv');
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

    public static function importSoftwares()
    {
        $file = public_path('storage/excel/Software_2025_05_30.csv');
        $import = new SoftwareHandoverImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importApollos()
    {
        $file = public_path('storage/excel/Apollo_2025_10_21.csv');
        $import = new ApolloImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importApollos1()
    {
        $file = public_path('storage/excel/Apollo_2025_10_21_1.csv');
        $import = new ApolloImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importApollos2()
    {
        $file = public_path('storage/excel/Apollo_2025_10_21_2.csv');
        $import = new ApolloImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importApollos3()
    {
        $file = public_path('storage/excel/Apollo_2025_10_21_3.csv');
        $import = new ApolloImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importApollos4()
    {
        $file = public_path('storage/excel/Apollo_2025_10_21_4.csv');
        $import = new ApolloImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }

    public static function importHrdfClaims()
    {
        $file = public_path('storage/excel/HRDF_Claims_Import.csv');
        $import = new HrdfClaimImport();
        Excel::import(import: $import, filePath: $file, readerType: \Maatwebsite\Excel\Excel::CSV);
    }
}
