<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;

class InvoiceOcrService
{
    /**
     * Extract ALL invoice numbers from multiple uploaded files
     *
     * @param array $filePaths Array of temporary file paths
     * @return string|null Comma-separated invoice numbers or null
     */
    public function extractInvoiceNumberFromMultipleFiles(array $filePaths): ?string
    {
        Log::info("🔍 Starting multi-file invoice scan", [
            'file_count' => count($filePaths),
            'files' => array_map(function($path) {
                return [
                    'path' => $path,
                    'exists' => file_exists($path),
                    'size' => file_exists($path) ? filesize($path) : 0
                ];
            }, $filePaths)
        ]);

        $foundInvoiceNumbers = []; // ✅ Collect ALL invoice numbers

        foreach ($filePaths as $index => $filePath) {
            Log::info("📄 Scanning file " . ($index + 1) . " of " . count($filePaths), [
                'file_index' => $index,
                'path' => $filePath,
                'exists' => file_exists($filePath),
                'size' => file_exists($filePath) ? filesize($filePath) : 0
            ]);

            $invoiceNumber = $this->extractInvoiceNumberFromTemp($filePath);

            if ($invoiceNumber) {
                Log::info("✅ Invoice number FOUND in file " . ($index + 1), [
                    'invoice_number' => $invoiceNumber,
                    'file_path' => $filePath,
                    'file_index' => $index
                ]);

                // ✅ Add to array instead of returning immediately
                if (!in_array($invoiceNumber, $foundInvoiceNumbers)) {
                    $foundInvoiceNumbers[] = $invoiceNumber;
                }
            } else {
                Log::info("❌ No invoice number found in file " . ($index + 1), [
                    'file_path' => $filePath,
                    'file_index' => $index
                ]);
            }
        }

        if (empty($foundInvoiceNumbers)) {
            Log::warning("⚠️ No invoice number found in ANY of the uploaded files", [
                'total_files_scanned' => count($filePaths)
            ]);
            return null;
        }

        // ✅ Return comma-separated list of ALL invoice numbers found
        $result = implode(', ', $foundInvoiceNumbers);

        Log::info("✅ All invoice numbers collected", [
            'total_files_scanned' => count($filePaths),
            'total_invoices_found' => count($foundInvoiceNumbers),
            'invoice_numbers' => $result
        ]);

        return $result;
    }

    /**
     * Extract invoice number from temporary uploaded file
     */
    public function extractInvoiceNumberFromTemp(string $tempPath): ?string
    {
        try {
            if (!file_exists($tempPath)) {
                Log::error("Temp file not found", ['path' => $tempPath]);
                return null;
            }

            $fileInfo = pathinfo($tempPath);
            $extension = $fileInfo['extension'] ?? '';

            Log::info("Processing temp file", [
                'path' => $tempPath,
                'extension' => $extension,
                'size' => filesize($tempPath)
            ]);

            // Convert PDF to image if needed
            if (strtolower($extension) === 'pdf' || $this->isPdf($tempPath)) {
                $imagePath = $this->convertPdfToImage($tempPath);
                if (!$imagePath) {
                    Log::warning("PDF conversion failed, attempting OCR on PDF directly");
                    return $this->extractFromPdfDirectly($tempPath);
                }
                $tempPath = $imagePath;
            }

            // Run OCR
            $ocr = new TesseractOCR($tempPath);
            $ocr->psm(6);
            $text = $ocr->run();

            Log::info("OCR text extracted", [
                'text_length' => strlen($text),
                'preview' => substr($text, 0, 200)
            ]);

            return $this->findInvoiceNumber($text);

        } catch (\Exception $e) {
            Log::error("OCR extraction failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Try to extract from PDF directly (fallback method)
     */
    protected function extractFromPdfDirectly(string $pdfPath): ?string
    {
        try {
            if ($this->commandExists('pdftotext')) {
                $outputPath = sys_get_temp_dir() . '/' . uniqid('pdf_text_') . '.txt';
                exec("pdftotext '$pdfPath' '$outputPath' 2>&1", $output, $returnCode);

                if ($returnCode === 0 && file_exists($outputPath)) {
                    $text = file_get_contents($outputPath);
                    unlink($outputPath);

                    Log::info("Extracted text using pdftotext", [
                        'text_length' => strlen($text),
                        'preview' => substr($text, 0, 200)
                    ]);

                    return $this->findInvoiceNumber($text);
                }
            }

            Log::warning("pdftotext not available or extraction failed");
            return null;

        } catch (\Exception $e) {
            Log::error("PDF direct extraction failed", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if a command exists in the system
     */
    protected function commandExists(string $command): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($command)));
        return !empty($return);
    }

    /**
     * Check if file is a PDF
     */
    protected function isPdf(string $filePath): bool
    {
        $handle = fopen($filePath, 'r');
        $header = fread($handle, 4);
        fclose($handle);
        return $header === '%PDF';
    }

    /**
     * Find invoice number pattern in OCR text
     */
    protected function findInvoiceNumber(string $text): ?string
    {
        $text = preg_replace('/\s+/', ' ', $text);

        Log::info("Searching for invoice number", [
            'text_sample' => substr($text, 0, 500)
        ]);

        // Pattern 1: "INV No." followed by colon and number
        if (preg_match('/INV\s+No\.?\s*:?\s*([A-Z0-9\-]+)/i', $text, $matches)) {
            Log::info("Invoice number found (Pattern 1)", ['number' => $matches[1]]);
            return trim($matches[1]);
        }

        // Pattern 2: "EHIN" prefix
        if (preg_match('/EHIN[\d\-]+/i', $text, $matches)) {
            Log::info("Invoice number found (Pattern 2)", ['number' => $matches[0]]);
            return trim($matches[0]);
        }

        // Pattern 3: "Invoice Number:"
        if (preg_match('/Invoice\s+Number\s*:?\s*([A-Z0-9\-]+)/i', $text, $matches)) {
            Log::info("Invoice number found (Pattern 3)", ['number' => $matches[1]]);
            return trim($matches[1]);
        }

        // Pattern 4: Near "INV" word
        if (preg_match('/INV[^\d]*([A-Z]*\d{4}[\-\d]+)/i', $text, $matches)) {
            Log::info("Invoice number found (Pattern 4)", ['number' => $matches[1]]);
            return trim($matches[1]);
        }

        // Pattern 5: EHIN-like pattern
        if (preg_match('/([A-Z]{2,4}\d{4}[\-\d]+)/i', $text, $matches)) {
            Log::info("Invoice number found (Pattern 5)", ['number' => $matches[1]]);
            return trim($matches[1]);
        }

        Log::warning("No invoice number pattern matched", [
            'text_sample' => substr($text, 0, 500)
        ]);
        return null;
    }

    /**
     * Convert PDF first page to image for OCR
     *
     * @param string $pdfPath
     * @return string|null
     */
    protected function convertPdfToImage(string $pdfPath): ?string
    {
        try {
            if (!extension_loaded('imagick')) {
                Log::warning("Imagick extension not installed");
                return null;
            }

            if (!class_exists('\Imagick')) {
                Log::warning("Imagick class not available");
                return null;
            }

            /** @var \Imagick $imagick */
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($pdfPath . '[0]');
            $imagick->setImageFormat('png');
            $imagick->setImageCompression(\Imagick::COMPRESSION_NO);

            $imagePath = sys_get_temp_dir() . '/' . uniqid('invoice_ocr_') . '.png';
            $imagick->writeImage($imagePath);
            $imagick->clear();
            $imagick->destroy();

            Log::info("PDF converted to image", [
                'pdf' => $pdfPath,
                'image' => $imagePath,
                'size' => filesize($imagePath)
            ]);

            return $imagePath;

        } catch (\Exception $e) {
            Log::error("PDF to image conversion failed", [
                'pdf' => $pdfPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
