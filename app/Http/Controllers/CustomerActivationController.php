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
    public function sendGroupActivationEmail($leadId, array $recipientEmails, $senderEmail = null, $senderName = null, $handoverId = null)
    {
        $lead = Lead::with('companyDetail')->findOrFail($leadId);

        // Generate random email and password for the customer account
        $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : $lead->company_name;
        $randomEmail = $this->generateRandomEmail($companyName, $handoverId);
        $randomPassword = $this->generateRandomPassword();

        // Check if customer already exists
        $customerExists = Customer::where('lead_id', $leadId)->first();

        if (!$customerExists) {
            // Check if the random email already exists
            while (Customer::where('email', $randomEmail)->exists()) {
                $randomEmail = $this->generateRandomEmail($companyName, $handoverId);
            }

            $customerName = $lead->companyDetail ? $lead->companyDetail->name : $lead->name;
            $customerPhone = $lead->companyDetail ? $lead->companyDetail->phone : $lead->phone;

            // Create customer record
            $customer = Customer::create([
                'name' => $customerName,
                'email' => $randomEmail,
                'original_email' => $recipientEmails[0], // Use first PIC email as original
                'lead_id' => $lead->id,
                'company_name' => $companyName,
                'phone' => $customerPhone,
                'password' => Hash::make($randomPassword),
                'status' => 'active',
                'email_verified_at' => Carbon::now()
            ]);
        } else {
            $customer = $customerExists;
            $randomEmail = $customer->email;
            $randomPassword = $this->generateRandomPassword();

            // Update password
            $customer->update([
                'password' => Hash::make($randomPassword)
            ]);
        }

        // Set sender details
        $fromEmail = $senderEmail ? $senderEmail : 'noreply@timeteccloud.com';
        $fromName = $senderName ? $senderName : 'TimeTec Implementation Team';

        $customerName = $lead->companyDetail ? $lead->companyDetail->name : $lead->name;
        $companyNameForEmail = $lead->companyDetail ? $lead->companyDetail->company_name : $lead->company_name;

        // Prepare CC recipients - include implementer and salesperson
        $ccRecipients = [];

        // Add implementer to CC
        if ($fromEmail && filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $ccRecipients[] = $fromEmail;
        }

        // Add salesperson to CC
        if ($lead->salesperson) {
            $salesperson = \App\Models\User::find($lead->salesperson);
            if ($salesperson && $salesperson->email && filter_var($salesperson->email, FILTER_VALIDATE_EMAIL)) {
                $ccRecipients[] = $salesperson->email;
            }
        }

        // Remove duplicates
        $ccRecipients = array_unique($ccRecipients);

        // Send email to all PICs with implementer as sender and CC implementer + salesperson
        \Illuminate\Support\Facades\Mail::send('emails.customer-activation', [
            'name' => $customerName,
            'email' => $randomEmail,
            'password' => $randomPassword,
            'company' => $companyNameForEmail,
            'implementer' => $senderName ? $senderName : 'TimeTec Implementation Team',
            'customer' => $customer,
            'loginEmail' => $randomEmail,
            'customerName' => $customerName,
            'companyName' => $companyNameForEmail,
            'implementerName' => $senderName,
            'loginUrl' => url('/customer/login'),
        ], function ($message) use ($recipientEmails, $fromEmail, $fromName, $companyNameForEmail, $ccRecipients) {
            $message->from($fromEmail, $fromName)
                    ->to($recipientEmails) // Send to all PICs
                    ->cc($ccRecipients) // CC the implementer + salesperson
                    ->subject("🚀 Customer Portal Access - " . $companyNameForEmail);
        });

        \Illuminate\Support\Facades\Log::info("Group activation email sent from {$fromEmail} to: " . implode(', ', $recipientEmails) . " | CC: " . implode(', ', $ccRecipients));

        return true;
    }

    private function generateRandomEmail($companyName = null, $handoverId = null)
    {
        // If handover ID is provided, use it to generate email
        if ($handoverId) {
            $handoverParts = explode('_', $handoverId);
            if (count($handoverParts) >= 2) {
                $yearAndId = $handoverParts[1]; // "250800"
                return strtolower("sw_{$yearAndId}@timeteccloud.com");
            }
        }

        // Fallback to original method if no handover ID
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

        return $username . '@timeteccloud.com';
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
