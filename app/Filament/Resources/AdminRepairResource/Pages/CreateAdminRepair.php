<?php

namespace App\Filament\Resources\AdminRepairResource\Pages;

use App\Filament\Resources\AdminRepairResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminRepair extends CreateRecord
{
    protected static string $resource = AdminRepairResource::class;
    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Process JSON encoding for array fields
        if (!empty($data['devices'])) {
            $devices = json_decode($data['devices'], true);
            if (is_array($devices) && !empty($devices[0])) {
                $data['device_model'] = $devices[0]['device_model'] ?? null;
                $data['device_serial'] = $devices[0]['device_serial'] ?? null;
            }
        }

        if (isset($data['remarks']) && is_array($data['remarks'])) {
            foreach ($data['remarks'] as $key => $remark) {
                // Encode the attachments array for each remark
                if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                    $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                }
            }
            // Encode the entire remarks structure
            $data['remarks'] = json_encode($data['remarks']);
        }

        if (isset($data['video_files'])) {
            if (is_array($data['video_files'])) {
                $data['video_files'] = json_encode($data['video_files']);
            } else if (is_string($data['video_files']) && !empty($data['video_files'])) {
                // Check if it's already valid JSON
                $decoded = json_decode($data['video_files'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Not valid JSON, encode it
                    $data['video_files'] = json_encode([$data['video_files']]);
                }
                // If it's already valid JSON, leave it as is
            }
        }

        // Store creator ID
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
