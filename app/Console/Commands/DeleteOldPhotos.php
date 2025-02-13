<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CleanUpOldPhotos extends Command
{
    protected $signature = 'cleanup:old-photos';
    protected $description = 'Delete orphaned photos from storage';

    public function handle()
    {
        // Get all file paths stored in the database (adjust the model and field as needed)
        $usedFiles = User::pluck('avatar')->filter()->toArray();

        // List all files in the directory (e.g., 'uploads/photos')
        $files = Storage::disk('public')->files('uploads/photos');

        $deleted = 0;
        foreach ($files as $file) {
            if (!in_array($file, $usedFiles)) {
                Storage::disk('public')->delete($file);
                $this->info("Deleted: {$file}");
                $deleted++;
            }
        }

        $this->info("Cleanup complete. Deleted {$deleted} files.");
    }
}