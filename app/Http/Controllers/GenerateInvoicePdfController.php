<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GenerateInvoicePdfController extends Controller
{
    public function __invoke($invoice_no)
    {
        dd($invoice_no);
        // Logic to generate and return the PDF for the given invoice number
        // For example, you might use a PDF library to create the PDF and return it as a response
        // Here's a simple placeholder implementation

        // Validate the invoice number (you might want to add more validation)
        if (empty($invoice_no)) {
            return response()->json(['error' => 'Invalid invoice number'], 400);
        }

        // Generate the PDF (this is just a placeholder, replace with actual PDF generation logic)
        $pdfContent = "PDF content for invoice number: " . $invoice_no;

        // Return the PDF as a download response
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="invoice_' . $invoice_no . '.pdf"');
    }
}
