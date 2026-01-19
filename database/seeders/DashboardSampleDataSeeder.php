<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DashboardSampleDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding dashboard sample data...');

        // Add completed software handovers for this month
        $this->seedSoftwareHandovers();
        
        // Add license certificates for new signups
        $this->seedLicenseCertificates();
        
        // Add active resellers
        $this->seedResellers();
        
        $this->command->info('Dashboard sample data seeded successfully!');
    }

    private function seedSoftwareHandovers()
    {
        // Get ANY leads (not just active ones)
        $leadIds = DB::table('leads')->limit(50)->pluck('id')->toArray();
        
        if (empty($leadIds)) {
            $this->command->warn('No leads found. Skipping software handovers.');
            return;
        }

        $thisMonth = Carbon::now()->startOfMonth();
        $count = 0;
        
        // Create software handovers for this month (for Trial to Paid metric)
        foreach ($leadIds as $leadId) {
            if ($count >= 20) break;
            
            // Check if handover already exists
            $exists = DB::table('software_handovers')
                ->where('lead_id', $leadId)
                ->where('status', 'completed')
                ->exists();
                
            if (!$exists) {
                try {
                    DB::table('software_handovers')->insert([
                        'lead_id' => $leadId,
                        'status' => 'completed',
                        'ta' => rand(0, 1),
                        'tl' => rand(0, 1),
                        'tc' => rand(0, 1),
                        'tp' => rand(0, 1),
                        'created_at' => $thisMonth->copy()->addDays(rand(1, 28)),
                        'updated_at' => now(),
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    // Skip if there are constraints
                }
            }
        }
        
        $this->command->info("✓ Added {$count} software handovers");
    }

    private function seedLicenseCertificates()
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $count = 0;
        
        // Create new license activations for this month
        for ($i = 0; $i < 30; $i++) {
            try {
                DB::table('license_certificates')->insert([
                    'company_name' => 'Demo Company ' . rand(1000, 9999),
                    'paid_license_start' => $thisMonth->copy()->addDays(rand(1, 28)),
                    'paid_license_end' => $thisMonth->copy()->addYear(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            } catch (\Exception $e) {
                // Skip if there are constraints
            }
        }
        
        $this->command->info("✓ Added {$count} license certificates");
    }

    private function seedResellers()
    {
        $count = 0;
        
        // Add active resellers
        for ($i = 0; $i < 15; $i++) {
            $email = 'demo.reseller' . $i . '@example.com';
            
            $exists = DB::table('reseller_v2')
                ->where('email', $email)
                ->exists();
                
            if (!$exists) {
                try {
                    DB::table('reseller_v2')->insert([
                        'name' => 'Demo Reseller ' . ($i + 1),
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'company_name' => 'Reseller Corp ' . ($i + 1),
                        'status' => 'active',
                        'sst_category' => 'NON-EXEMPTED',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    $this->command->warn("Skip reseller {$i}: " . $e->getMessage());
                }
            }
        }
        
        $this->command->info("✓ Added {$count} active resellers");
    }
}
