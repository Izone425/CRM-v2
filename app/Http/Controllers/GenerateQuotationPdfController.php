<?php

namespace App\Http\Controllers;


use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Quotation;
use App\Models\User;

class GenerateQuotationPdfController extends Controller
{
    public function __invoke(Quotation $quotation)
    {
        info(auth('web')->user()->role);
        info($quotation->sales_person_id);
        if (auth('web')->user()->role == User::IS_USER && auth('web')->user()->id <> $quotation->sales_person_id) {
            abort(401);
        }

        $image = file_get_contents(public_path('/img/logo-ttc.png'));
        $image2 = file_get_contents(public_path('/img/tc-chop.png'));

        $signature = null;

        /**
         * if signature image exists
         */
        if ($quotation->sales_person && $quotation->sales_person->signature_path) {
            $signaturePath = public_path('storage/' . $quotation->sales_person->signature_path);

            if (file_exists($signaturePath)) {
                $signatureImage = file_get_contents($signaturePath);
                $signature = 'data:image/png;base64,' . base64_encode($signatureImage);
            }
        }

        $img_base_64 = base64_encode($image);
        $img_base_64_2 = base64_encode($image2);

        $path_img = 'data:image/png;base64,' . $img_base_64;
        $path_img2 = 'data:image/png;base64,' . $img_base_64_2;


        view()->share('pdf.quotation-v2', compact('quotation','path_img'));
        $pdf = Pdf::setOptions(['isPhpEnabled' => true,'isRemoteEnabled' => true])->loadView('pdf.quotation-v2', compact('quotation','path_img','path_img2','signature'));
        $pdf->set_paper('a4', 'portrait');

        /**
         * save a copy of quotation in public storage
         */
        //$quotationFilename = Str::replace('-','_',Str::slug($quotation->company->name)) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
        //$quotationFilename = Str::replace('-','_',Str::slug($quotation->company->name)) . '_' . quotation_reference_no($quotation->id) . '_' .
        $quotationFilename = 'TIMETEC_' . $quotation->sales_person->code . '_' . quotation_reference_no($quotation->id) . '_' . Str::replace('-','_',Str::slug($quotation->lead->company_name));
        $quotationFilename = Str::upper($quotationFilename) . '.pdf';
        $pdf->save(public_path('/storage/quotations/'.$quotationFilename));

        return $pdf->stream($quotationFilename, array("Attachment" => false));
    }
}
