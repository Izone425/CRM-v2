<?php

namespace Database\Seeders;

use App\Models\HrLicense;
use App\Models\HrTerminalDevice;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HrTerminalDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $licenses = HrLicense::all();
        $now = Carbon::now();
        $models = ['FaceId 5', 'FaceID 6', 'TimeTec Face', 'i-Kadex', 'k-Kadex'];

        foreach ($licenses as $license) {
            $deviceCount = rand(2, 4);

            for ($i = 0; $i < $deviceCount; $i++) {
                $invoiceNo = (!empty($license->invoice_no) && $license->invoice_no !== '-')
                    ? $license->invoice_no
                    : null;

                HrTerminalDevice::create([
                    'software_handover_id' => $license->software_handover_id,
                    'handover_id' => $license->handover_id,
                    'company_name' => $license->company_name,
                    'invoice_no' => $invoiceNo,
                    'model' => $models[array_rand($models)],
                    'serial_no' => $this->generateSerialNo(),
                    'backend_device_id' => rand(0, 3) > 0 ? (string) rand(2900, 3100) : null,
                    'status' => rand(1, 10) <= 8 ? 'Enabled' : 'Disabled',
                    'created_at' => $now->copy()->subMinutes(rand(0, 10080)),
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function generateSerialNo(): string
    {
        $prefixes = ['', 'UDP', 'TC', 'FID'];
        $prefix = $prefixes[array_rand($prefixes)];

        if ($prefix) {
            return $prefix . rand(1000000000, 9999999999);
        }

        return (string) rand(1000000000, 9999999999);
    }
}
