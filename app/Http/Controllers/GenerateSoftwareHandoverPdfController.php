<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Models\SoftwareHandover;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GenerateSoftwareHandoverPdfController extends Controller
{
    public function __invoke(SoftwareHandover $softwareHandover)
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
            $creator = User::find($softwareHandover->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            // Load the lead/company data
            $lead = $softwareHandover->lead;

            // Generate PDF
            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.software-handover', compact('softwareHandover', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // UPDATED: Generate a filename for the handover PDF using the new format
            $creatorCode = $creator->code;
            $year = date('y', strtotime($softwareHandover->created_at));
            $id = str_pad($softwareHandover->id, 4, '0', STR_PAD_LEFT);

            // Format: TTC/SH/NN/250001
            $formattedId = "{$year}{$id}";

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

            Log::info('Software PDF saved successfully', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the handover record with the PDF path
            $softwareHandover->update([
                'handover_pdf' => $filePath
            ]);

            // Return streaming response
            return $pdf->stream($handoverFilename);

        } catch (\Exception $e) {
            Log::error('Error generating software PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'handover_id' => $softwareHandover->id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->view('errors.pdf-generation-failed', [
                'message' => $e->getMessage(),
                'handoverId' => $softwareHandover->id
            ], 500);
        }
    }

    public function generateInBackground(SoftwareHandover $softwareHandover)
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
            $creator = User::find($softwareHandover->created_by);

            if ($creator && $creator->signature_path) {
                $signaturePath = public_path('storage/' . $creator->signature_path);

                if (file_exists($signaturePath)) {
                    $signatureImage = file_get_contents($signaturePath);
                    $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
                }
            }

            $lead = $softwareHandover->lead;

            $pdf = Pdf::setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ])->loadView('pdf.software-handover', compact('softwareHandover', 'path_img', 'path_img2', 'signature', 'lead', 'creator'));

            $pdf->setPaper('a4', 'portrait');

            // UPDATED: Generate a filename for the handover PDF using the new format
            $creatorCode = $creator->code;
            $year = date('y', strtotime($softwareHandover->created_at));
            $id = str_pad($softwareHandover->id, 4, '0', STR_PAD_LEFT);

            // Format: TTC/SH/NN/250001
            $formattedId = "{$year}{$id}";

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

            Log::info('Software PDF saved successfully in background', [
                'path' => $filePath,
                'exists' => File::exists(storage_path('app/public/' . $filePath)),
                'formatted_id' => $formattedId
            ]);

            // Update the handover record with the PDF path
            $softwareHandover->update([
                'handover_pdf' => $filePath
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error generating software PDF in background', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'handover_id' => $softwareHandover->id
            ]);

            return null;
        }
    }
}
