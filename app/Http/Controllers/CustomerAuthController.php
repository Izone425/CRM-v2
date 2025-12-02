<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CustomerAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('customer.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('customer')->attempt([
            'email' => $request->email,
            'password' => $request->password
        ], $request->filled('remember'))) {

            // Update last login timestamp
            $customer = Customer::where('email', $request->email)->first();
            if ($customer) {
                $customer->last_login_at = Carbon::now();
                $customer->save();
            }

            return redirect()->intended(route('customer.dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
