<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandover;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverController extends Controller
{
    public function getCounts()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'pending_confirmation' => 0,
                'pending_reseller_invoice' => 0,
                'pending_payment' => 0,
                'pending_timetec_license' => 0,
                'completed' => 0
            ]);
        }

        $resellerId = $reseller->reseller_id;

        return response()->json([
            'pending_confirmation' => ResellerHandover::where('reseller_id', $resellerId)
                ->where('status', 'pending_confirmation')
                ->count(),
            'pending_reseller_invoice' => ResellerHandover::where('reseller_id', $resellerId)
                ->where('status', 'pending_reseller_invoice')
                ->count(),
            'pending_payment' => ResellerHandover::where('reseller_id', $resellerId)
                ->where('status', 'pending_payment')
                ->count(),
            'pending_timetec_license' => ResellerHandover::where('reseller_id', $resellerId)
                ->whereIn('status', ['pending_timetec_license', 'new', 'pending_timetec_invoice'])
                ->count(),
            'completed' => ResellerHandover::where('reseller_id', $resellerId)
                ->where('status', 'completed')
                ->count()
        ]);
    }

    public function getAdminCounts()
    {
        return response()->json([
            'new' => ResellerHandover::where('status', 'new')->count(),
            'pending_timetec_invoice' => ResellerHandover::where('status', 'pending_timetec_invoice')->count(),
            'pending_timetec_license' => ResellerHandover::where('status', 'pending_timetec_license')->count(),
            'completed' => ResellerHandover::where('status', 'completed')->count(),
        ]);
    }
}
