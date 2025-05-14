<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerActivationMail;
use App\Models\Lead;

class CustomerActivationController extends Controller
{
    public function sendActivationEmail($leadId)
    {
        $lead = Lead::with('companyDetail')->findOrFail($leadId);

        // Generate a unique token
        $token = Str::random(64);

        // Check if customer already exists
        $customerExists = Customer::where('email', $lead->companyDetail->email)->exists();
        if ($customerExists) {
            return back()->with('error', 'Customer with this email already exists.');
        }

        // Create customer record with pending status
        $customer = Customer::create([
            'name' => $lead->companyDetail->name ?? $lead->name,
            'email' => $lead->companyDetail->email ?? $lead->email,
            'company_name' => $lead->companyDetail->company_name ?? $lead->company_name,
            'password' => Hash::make(Str::random(16)), // temporary password
            'activation_token' => $token,
            'token_expires_at' => Carbon::now()->addHours(24),
            'status' => 'pending'
        ]);

        // Send activation email
        Mail::to($lead->companyDetail->email ?? $lead->email)->send(new CustomerActivationMail(
            $customer,
            $token,
            $lead->companyDetail->name ?? $lead->name
        ));

        return back()->with('success', 'Activation email has been sent to the customer.');
    }

    public function activateAccount($token)
    {
        $customer = Customer::where('activation_token', $token)
                         ->where('token_expires_at', '>', Carbon::now())
                         ->first();

        if (!$customer) {
            return redirect()->route('customer.login')
                ->with('error', 'Invalid or expired activation link.');
        }

        return view('customer.activate-account', compact('customer', 'token'));
    }

    public function completeActivation(Request $request, $token)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $customer = Customer::where('activation_token', $token)
                        ->where('token_expires_at', '>', Carbon::now())
                        ->first();

        if (!$customer) {
            return redirect()->route('customer.login')
                ->with('error', 'Invalid or expired activation link.');
        }

        // Update customer with new password
        $customer->update([
            'password' => Hash::make($request->password),
            'activation_token' => null,
            'token_expires_at' => null,
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);

        // Instead of auto login, redirect to login page with success message
        return redirect()->route('customer.login')
            ->with('success', 'Your account has been activated successfully. Please login to continue.');
    }
}
