<?php

namespace App\Filament\Resources\AdminRepairResource\Pages;

use App\Filament\Resources\AdminRepairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminRepair extends EditRecord
{
    protected static string $resource = AdminRepairResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\DeleteAction::make(),
    //     ];
    // }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add debugging
        \Illuminate\Support\Facades\Log::info('EditAdminRepair data before fill:', [
            'remarks_type' => isset($data['remarks']) ? gettype($data['remarks']) : 'not set',
        ]);

        // Ensure remarks is decoded if it's a string
        if (isset($data['remarks']) && is_string($data['remarks'])) {
            $decoded = json_decode($data['remarks'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Process each remark to ensure attachments are properly decoded
                foreach ($decoded as $key => $remark) {
                    if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                        $attachmentsDecoded = json_decode($remark['attachments'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decoded[$key]['attachments'] = $attachmentsDecoded;
                        } else {
                            $decoded[$key]['attachments'] = [];
                        }
                    }
                }

                // Replace the data
                $data['remarks'] = $decoded;
            } else {
                // If there was an error decoding, initialize as empty array
                $data['remarks'] = [];
            }
        }

        // Ensure video_files is decoded if it's a string
        if (isset($data['video_files']) && is_string($data['video_files'])) {
            $decoded = json_decode($data['video_files'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['video_files'] = $decoded;
            } else {
                // If JSON decode fails, initialize as empty array
                $data['video_files'] = [];
            }
        }

        return $data;
    }
}
