<?php

namespace App\Console\Commands;

use App\Imports\SoftwareHandoverImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportSoftwareHandovers extends Command
{
    protected $signature = 'import:software-handovers {file}';
    protected $description = 'Import software handovers from Excel file';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File does not exist: {$file}");
            return 1;
        }

        $this->info('Starting import...');

        try {
            Excel::import(new SoftwareHandoverImport, $file);
            $this->info('Import completed successfully!');
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
