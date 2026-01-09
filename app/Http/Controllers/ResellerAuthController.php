<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ResellerV2;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ResellerAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('reseller.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('reseller')->attempt([
            'email' => $request->email,
            'password' => $request->password
        ], $request->filled('remember'))) {

            // Update last login timestamp
            $reseller = ResellerV2::where('email', $request->email)->first();
            if ($reseller) {
                $reseller->last_login_at = Carbon::now();
                $reseller->save();
            }

            return redirect()->intended(route('reseller.dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('reseller')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('reseller.login');
    }
}
