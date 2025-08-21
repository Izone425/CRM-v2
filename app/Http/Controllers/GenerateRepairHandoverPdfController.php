<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Models\AdminRepair;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GenerateRepairHandoverPdfController extends Controller
{
    public function __invoke(AdminRepair $repair)
    {
        try {
            // Load company logo
            $image = file_get_contents(public_path('/img/logo-ttc.png'));
            $img_base_64 = base64_encode($image);
            $path_img = 'data:image/png;base64,' . $img_base_64;

            $path_img2 = null;
            try {
                $image2 = file_get_contents(public_path('/img/tc-chop.png'));
                $img_base_64_2 = base64_encode($image2);
                $path_img2 = 'data:image/png;base64,' . $img_base_64_2;
            } catch (\Exception $e) {
                Log::warning('Failed to load chop image', ['error' => $e->getMessage()]);
            }

            // Get creator signature if exists
            $signature = null;
            $creator = User::find($repair->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            // Load the lead/company data
            $lead = $repair->lead;

            // Generate repair ID
            $repairId = 'OR_250' . str_pad($repair->id, 3, '0', STR_PAD_LEFT);

            // Generate PDF
            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.repair-handover', compact('repair', 'repairId', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // Generate a filename for the repair PDF
            $year = date('y', strtotime($repair->created_at));
            $id = str_pad($repair->id, 3, '0', STR_PAD_LEFT);

            // Format: OR_yynnn
            $formattedId = "OR_{$year}{$id}";

            // For the filename, replace slashes with underscores to avoid file system issues
            $repairFilename = str_replace('/', '_', $formattedId) . '.pdf';

            // Storage directory structure
            $storageDir = 'repairs/pdf';
            $filePath = $storageDir . '/' . $repairFilename;
            $fullStoragePath = storage_path('app/public/' . $storageDir);

            // Make sure the directory exists with proper permissions
            if (!File::isDirectory($fullStoragePath)) {
                File::makeDirectory($fullStoragePath, 0755, true);
            }

            // Save the PDF directly to the storage path
            $pdf->save(storage_path('app/public/' . $filePath));

            Log::info('Repair PDF saved successfully', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the repair record with the PDF path
            $repair->update([
                'handover_pdf' => $filePath
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error generating repair PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'repair_id' => $repair->id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->view('errors.pdf-generation-failed', [
                'message' => $e->getMessage(),
                'repairId' => $repair->id
            ], 500);
        }
    }

    public function generateInBackground(AdminRepair $repair)
    {
        try {
            // Load company logo
            $image = file_get_contents(public_path('/img/logo-ttc.png'));
            $img_base_64 = base64_encode($image);
            $path_img = 'data:image/png;base64,' . $img_base_64;

            $path_img2 = null;
            try {
                $image2 = file_get_contents(public_path('/img/tc-chop.png'));
                $img_base_64_2 = base64_encode($image2);
                $path_img2 = 'data:image/png;base64,' . $img_base_64_2;
            } catch (\Exception $e) {
                Log::warning('Failed to load chop image', ['error' => $e->getMessage()]);
            }

            // Get creator signature if exists
            $signature = null;
            $creator = User::find($repair->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            // Load the lead/company data
            $lead = $repair->lead;

            // Generate repair ID
            $repairId = 'OR_250' . str_pad($repair->id, 3, '0', STR_PAD_LEFT);

            // Generate PDF
            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.repair-handover', compact('repair', 'repairId', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // Generate a filename for the repair PDF
            $year = date('y', strtotime($repair->created_at));
            $id = str_pad($repair->id, 3, '0', STR_PAD_LEFT);

            // Format: OR_yynnn
            $formattedId = "OR_{$year}{$id}";

            // For the filename, replace slashes with underscores to avoid file system issues
            $repairFilename = str_replace('/', '_', $formattedId) . '.pdf';

            // Storage directory structure
            $storageDir = 'repairs/pdf';
            $filePath = $storageDir . '/' . $repairFilename;
            $fullStoragePath = storage_path('app/public/' . $storageDir);

            // Make sure the directory exists with proper permissions
            if (!File::isDirectory($fullStoragePath)) {
                File::makeDirectory($fullStoragePath, 0755, true);
            }

            // Save the PDF directly to the storage path
            $pdf->save(storage_path('app/public/' . $filePath));

            Log::info('Repair PDF saved successfully in background', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the repair record with the PDF path
            $repair->update([
                'handover_pdf' => $filePath
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error generating repair PDF in background', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'repair_id' => $repair->id
            ]);

            return null;
        }
    }
}
