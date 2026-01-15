<?php

namespace App\Http\Controllers;

use App\Models\FinanceInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerateFinanceInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(FinanceInvoice $financeInvoice)
    {
        $image = file_get_contents(public_path('/img/logo-ttc.png'));
        $img_base_64 = base64_encode($image);
        $path_img = 'data:image/png;base64,' . $img_base_64;

        view()->share('finance-invoice', compact('financeInvoice', 'path_img'));
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])
            ->loadView('pdf.finance-invoice', compact('financeInvoice', 'path_img'));
        $pdf->set_paper('a4', 'portrait');

        /**
         * save a copy of finance invoice in public storage
         */
        $invoiceFilename = 'FI_' . $financeInvoice->fc_number . '_' .
            Str::replace('-', '_', Str::slug($financeInvoice->reseller_name));
        $invoiceFilename = Str::upper($invoiceFilename) . '.pdf';
        $pdf->save(public_path('/storage/finance-invoices/' . $invoiceFilename));

        return $pdf->stream($invoiceFilename, array("Attachment" => false));
    }
}
