<?php
// filepath: /var/www/html/timeteccrm/app/Exports/HandoversExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HandoversExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $handovers;
    protected $title;

    public function __construct($handovers, $title = 'Handovers Export')
    {
        $this->handovers = $handovers;
        $this->title = $title;
    }

    public function collection()
    {
        // If grouped data, flatten it
        if ($this->handovers instanceof \Illuminate\Support\Collection &&
            $this->handovers->first() instanceof \Illuminate\Support\Collection) {
            $flattened = collect();
            foreach ($this->handovers as $companySize => $items) {
                foreach ($items as $item) {
                    $item->grouped_company_size = $companySize;
                    $flattened->push($item);
                }
            }
            return $flattened;
        }

        return $this->handovers;
    }

    public function headings(): array
    {
        return [
            // 'No.',
            'Implementer',
            'Company Size',
            'Company Name',
            // 'Status',
            // 'Headcount',
            // 'Lead ID'
        ];
    }

    public function map($handover): array
    {
        static $counter = 0;
        $counter++;

        // Determine company size from headcount if not already grouped
        $companySize = $handover->grouped_company_size ?? $this->determineCompanySize($handover->headcount);

        return [
            // $counter,
            $handover->implementer ?? 'N/A',
            $companySize,
            $handover->company_name ?? 'N/A',
            // $handover->status_handover ?? 'N/A',
            // $handover->headcount ?? 'N/A',
            // $handover->lead_id ?? 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => ['font' => ['bold' => true, 'size' => 12]],

            // Auto-size columns
            'A:G' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    private function determineCompanySize($headcount)
    {
        if ($headcount === null || $headcount === '') {
            return 'Unknown';
        }

        $headcount = (int)$headcount;

        if ($headcount >= 1 && $headcount <= 24) {
            return 'Small (1-24)';
        } elseif ($headcount >= 25 && $headcount <= 99) {
            return 'Medium (25-99)';
        } elseif ($headcount >= 100 && $headcount <= 500) {
            return 'Large (100-500)';
        } elseif ($headcount > 500) {
            return 'Enterprise (501+)';
        }

        return 'Unknown';
    }
}
