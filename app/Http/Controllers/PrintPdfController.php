<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PrintPdfController extends Controller
{
    public function __invoke(Quotation $quotation)
    {
        // info(auth('web')->user()->role);
        // info($quotation->sales_person_id);
        if (auth('web')->user()->role == User::IS_USER && auth('web')->user()->id <> $quotation->sales_person_id) {
            abort(401);
        }

        $image = file_get_contents(public_path('/img/logo-ttc.png'));
        $image2 = file_get_contents(public_path('/img/tc-chop.png'));

        $signature = null;

        /**
         * if signature image exists
         */
        if ($quotation->sales_person->signature) {
            $signatureImg = public_path('/img/'.$quotation->sales_person->signature);
            if (file_exists($signatureImg)) {
                $image3 = file_get_contents($signatureImg);
                $img_base_64_3 = base64_encode($image3);
                $signature = 'data:image/png;base64,' . $img_base_64_3;
            }
        }

        $img_base_64 = base64_encode($image);
        $img_base_64_2 = base64_encode($image2);

        $path_img = 'data:image/png;base64,' . $img_base_64;
        $path_img2 = 'data:image/png;base64,' . $img_base_64_2;


        view()->share('quotation', compact('quotation','path_img'));
        $pdf = Pdf::setOptions(['isPhpEnabled' => true,'isRemoteEnabled' => true])->loadView('pdf.quotation', compact('quotation','path_img','path_img2','signature'));
        $pdf->set_paper('a4', 'portrait');

        /**
         * save a copy of quotation in public storage
         */
        //$quotationFilename = Str::replace('-','_',Str::slug($quotation->company->name)) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
        //$quotationFilename = Str::replace('-','_',Str::slug($quotation->company->name)) . '_' . quotation_reference_no($quotation->id) . '_' .
        $quotationFilename = 'TIMETEC_' . $quotation->sales_person->code . '_' . quotation_reference_no($quotation->id) . '_' . Str::replace('-','_',Str::slug($quotation->lead->companyDetail->company_name));
        $quotationFilename = Str::upper($quotationFilename) . '.pdf';
        $pdf->save(public_path('/storage/quotations/'.$quotationFilename));

        return $pdf->stream($quotationFilename, array("Attachment" => false));
    }
}
