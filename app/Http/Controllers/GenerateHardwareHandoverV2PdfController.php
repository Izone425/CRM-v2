<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Models\HardwareHandoverV2;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GenerateHardwareHandoverV2PdfController extends Controller
{
    public function __invoke(HardwareHandoverV2 $hardwareHandover)
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
            $creator = User::find($hardwareHandover->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            // Load the lead/company data
            $lead = $hardwareHandover->lead;

            // Generate PDF
            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.hardware-handover-v2', compact('hardwareHandover', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // UPDATED: Generate a filename for the handover PDF using the new format
            $creatorCode = $creator->code;
            $year = date('y', strtotime($hardwareHandover->created_at));
            $id = str_pad($hardwareHandover->id, 4, '0', STR_PAD_LEFT);

            // Format: TTC/HH/NN/250001
            $formattedId = "HW/{$year}{$id}";

            // For the filename, replace slashes with underscores to avoid file system issues
            $handoverFilename = str_replace('/', '_', $formattedId) . '.pdf';

            // Storage directory structure
            $storageDir = 'handovers/pdf';
            $filePath = $storageDir . '/' . $handoverFilename;
            $fullStoragePath = storage_path('app/public/' . $storageDir);

            // Make sure the directory exists with proper permissions
            if (!File::isDirectory($fullStoragePath)) {
                File::makeDirectory($fullStoragePath, 0755, true);
            }

            // Save the PDF directly to the storage path
            $pdf->save(storage_path('app/public/' . $filePath));

            Log::info('Hardware PDF saved successfully', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the handover record with the PDF path
            $hardwareHandover->update([
                'handover_pdf' => $filePath
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error generating hardware PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'handover_id' => $hardwareHandover->id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->view('errors.pdf-generation-failed', [
                'message' => $e->getMessage(),
                'handoverId' => $hardwareHandover->id
            ], 500);
        }
    }

    public function generateInBackground(HardwareHandoverV2 $hardwareHandover)
    {
        try {
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

            $signature = null;
            $creator = User::find($hardwareHandover->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            $lead = $hardwareHandover->lead;

            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.hardware-handover-v2', compact('hardwareHandover', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // UPDATED: Generate a filename for the handover PDF using the new format
            $creatorCode = $creator->code;
            $year = date('y', strtotime($hardwareHandover->created_at));
            $id = str_pad($hardwareHandover->id, 4, '0', STR_PAD_LEFT);

            // Format: TTC/HH/NN/250001
            $formattedId = "HW/{$year}{$id}";

            // For the filename, replace slashes with underscores to avoid file system issues
            $handoverFilename = str_replace('/', '_', $formattedId) . '.pdf';

            // Storage directory structure
            $storageDir = 'handovers/pdf';
            $filePath = $storageDir . '/' . $handoverFilename;
            $fullStoragePath = storage_path('app/public/' . $storageDir);

            // Make sure the directory exists with proper permissions
            if (!File::isDirectory($fullStoragePath)) {
                File::makeDirectory($fullStoragePath, 0755, true);
            }

            // Save the PDF directly to the storage path
            $pdf->save(storage_path('app/public/' . $filePath));

            Log::info('Hardware PDF saved successfully in background', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the handover record with the PDF path
            $hardwareHandover->update([
                'handover_pdf' => $filePath
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error generating hardware PDF in background', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'handover_id' => $hardwareHandover->id
            ]);

            return null;
        }
    }
}
