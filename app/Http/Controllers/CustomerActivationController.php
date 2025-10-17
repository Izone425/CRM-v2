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

        // Generate random email and password
        $randomEmail = $this->generateRandomEmail($lead->companyDetail->company_name ?? $lead->company_name);
        $randomPassword = $this->generateRandomPassword();

        // Check if customer already exists with the original email
        $customerExists = Customer::where('email', $lead->companyDetail->email)->exists();
        if ($customerExists) {
            return back()->with('error', 'Customer with this email already exists.');
        }

        // Check if the random email already exists (very unlikely but safe to check)
        while (Customer::where('email', $randomEmail)->exists()) {
            $randomEmail = $this->generateRandomEmail($lead->companyDetail->company_name ?? $lead->company_name);
        }

        // Create customer record with active status
        $customer = Customer::create([
            'name' => $lead->companyDetail->name ?? $lead->name,
            'email' => $randomEmail, // Use random email for login
            'original_email' => $lead->companyDetail->email ?? $lead->email, // Keep original for reference
            'lead_id' => $lead->id,
            'company_name' => $lead->companyDetail->company_name ?? $lead->company_name,
            'phone' => $lead->companyDetail->phone ?? $lead->phone,
            'password' => Hash::make($randomPassword),
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);

        // Send credentials email to the original email address
        Mail::to($lead->companyDetail->email ?? $lead->email)->send(new CustomerActivationMail(
            $customer,
            $randomEmail,
            $randomPassword,
            $lead->companyDetail->name ?? $lead->name
        ));

        return back()->with('success', 'Customer account has been created and credentials have been sent via email.');
    }

    private function generateRandomEmail($companyName = null)
    {
        // Clean company name for email generation
        $cleanCompanyName = '';
        if ($companyName) {
            $cleanCompanyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));
            $cleanCompanyName = substr($cleanCompanyName, 0, 8); // Limit to 8 characters
        }

        // Generate random string
        $randomString = strtolower(Str::random(6));

        // Create email with company name prefix or just random
        if ($cleanCompanyName) {
            $username = $cleanCompanyName . $randomString;
        } else {
            $username = 'customer' . $randomString . rand(100, 999);
        }

        return $username . '@timeteccustomer.com';
    }

    private function generateRandomPassword($length = 12)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';

        // Ensure password has at least one uppercase, one lowercase, one digit, and one special character
        $password .= $characters[rand(26, 51)]; // Uppercase
        $password .= $characters[rand(0, 25)];  // Lowercase
        $password .= $characters[rand(52, 61)]; // Digit
        $password .= $characters[rand(62, strlen($characters) - 1)]; // Special character

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return str_shuffle($password);
    }

    // Remove the old activation methods as they're no longer needed
    public function activateAccount($token)
    {
        return redirect()->route('customer.login')
            ->with('info', 'Account activation is no longer required. Please use your credentials to login.');
    }

    public function completeActivation(Request $request, $token)
    {
        return redirect()->route('customer.login')
            ->with('info', 'Account activation is no longer required. Please use your credentials to login.');
    }
}
