<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TicketAnalysisExport implements WithMultipleSheets
{
    protected $ticketsByPriority;
    protected $moduleTitle;

    public function __construct(array $ticketsByPriority, string $moduleTitle)
    {
        $this->ticketsByPriority = $ticketsByPriority;
        $this->moduleTitle = $moduleTitle;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->ticketsByPriority as $priorityGroup) {
            $sheets[] = new TicketPrioritySheet(
                $priorityGroup['tickets'],
                $priorityGroup['name'],
                $this->moduleTitle
            );
        }

        return $sheets;
    }
}
