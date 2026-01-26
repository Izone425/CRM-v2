<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class TicketPrioritySheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $tickets;
    protected $priorityName;
    protected $moduleTitle;

    public function __construct(array $tickets, string $priorityName, string $moduleTitle)
    {
        $this->tickets = $tickets;
        $this->priorityName = $priorityName;
        $this->moduleTitle = $moduleTitle;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->tickets as $ticket) {
            $data[] = [
                $ticket['ticket_id'] ?? 'N/A',
                $ticket['title'] ?? 'No Title',
                $ticket['company_name'] ?? 'N/A',
                $ticket['status'] ?? 'N/A',
                isset($ticket['created_date']) ? Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A',
            ];
        }

        return $data;
    }

    public function title(): string
    {
        // Excel sheet names have a 31 character limit and cannot contain special characters
        $title = preg_replace('/[\\\\\/\*\?\[\]\:]/', '', $this->priorityName);
        return substr($title, 0, 31);
    }

    public function headings(): array
    {
        return [
            'Ticket ID',
            'Title',
            'Company',
            'Status',
            'Created Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Get the last row number
        $lastRow = count($this->tickets) + 1;

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $this->getPriorityColor()],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // All cells border
            "A1:E{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }

    private function getPriorityColor(): string
    {
        $colors = [
            'Software Bugs' => 'EF4444',
            'Back End Assistance' => 'F59E0B',
            'Critical Enhancement' => '8B5CF6',
            'Non-Critical Enhancement' => '10B981',
            'Paid Customization' => '3B82F6',
        ];

        return $colors[$this->priorityName] ?? '6B7280';
    }
}
