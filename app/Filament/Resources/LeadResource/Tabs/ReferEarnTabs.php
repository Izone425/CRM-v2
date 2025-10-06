<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReferEarnTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1) // Main grid for all sections
                ->schema([
                    // First row: Referral Details (From and Refer To)
                    Grid::make(2) // Three columns layout: From, Arrow, Refer To
                        ->schema([
                            // From Section
                            Section::make('From')
                                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                                ->schema([
                                    View::make('components.referral-from-section')
                                ])
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ])
                                ->headerActions([
                                    Action::make('update_closed_by')
                                        ->label('Update Closed By')
                                        ->icon('heroicon-o-user-circle')
                                        ->color('primary')
                                        ->visible(fn () => in_array(auth()->user()->role_id, [1, 3])) // Only for Admin (1) and Manager (3)
                                        ->modalHeading('Edit Referral Closed By')
                                        ->modalSubmitActionLabel('Save')
                                        ->form([
                                            Select::make('closed_by')
                                                ->label('Closed By')
                                                ->options(function () {
                                                    return \App\Models\User::where('role_id', 2)
                                                        ->where('is_timetec_hr', true)
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->required()
                                                ->preload()
                                                ->default(function ($record) {
                                                    return $record->referralDetail?->closed_by ?? null;
                                                })
                                        ])
                                        ->action(function (Lead $record, array $data) {
                                            $record->updateQuietly([
                                                'closed_by' => $data['closed_by'],
                                            ]);

                                            // Log the activity
                                            activity()
                                                ->performedOn($record)
                                                ->causedBy(auth()->user())
                                                ->withProperties(['closed_by' => $data['closed_by']])
                                                ->log('Updated referral closed by information');

                                            Notification::make()
                                                ->title('Referral Closed By Updated')
                                                ->success()
                                                ->send();
                                        })
                                ]),

                            Section::make('Refer to')
                                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                                ->schema([
                                    View::make('components.referral-to-section')
                                    // Placeholder::make('to_company')
                                    //     ->label('COMPANY')
                                    //     ->content(fn ($record) => $record->companyDetail->company_name ?? null),
                                    // Placeholder::make('to_name')
                                    //     ->label('NAME')
                                    //     ->content(fn ($record) => $record->name ?? null),
                                    // Placeholder::make('to_email')
                                    //     ->label('EMAIL ADDRESS')
                                    //     ->content(fn ($record) => $record->email ?? null),
                                    // Placeholder::make('to_contact')
                                    //     ->label('CONTACT NO.')
                                    //     ->content(fn ($record) => $record->phone ?? null),
                                ])
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                                ]),
                        ])
                        ->columns(2),

                    // Second row: Bank Details
                    Section::make('Bank Details')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->extraAttributes([
                            'style' => 'background-color: #e6e6fa4d; border: dashed; border-color: #cdcbeb;'
                        ])
                        ->headerActions([
                            Action::make('export_bank_details')
                                ->label('Export to Excel')
                                ->icon('heroicon-o-document-arrow-down')
                                ->color('success')
                                ->visible(fn () => auth()->user()->role_id === 3)
                                ->action(function (Lead $lead) {
                                    return static::exportBankDetailsToExcel($lead);
                                }),
                            Action::make('edit_bank_detail')
                            ->label('Edit') // Button label
                            ->icon('heroicon-o-pencil')
                            ->visible(fn () => auth()->user()->role_id === 3)
                            ->modalHeading('Edit Referral Details') // Modal heading
                            ->modalSubmitActionLabel('Save Changes') // Modal button text
                            ->form([
                                Tabs::make('bank_details_tabs')
                                    ->tabs([
                                        Tabs\Tab::make('Referral Details')
                                            ->schema([
                                                TextInput::make('referral_name')
                                                    ->label('Referral Name')
                                                    ->required()
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->referral_name ?? $record?->bankDetail?->full_name ?? null),

                                                TextInput::make('tin')
                                                    ->label('Tax Identification Number')
                                                    ->default(fn ($record) => $record?->bankDetail?->tin ?? null),

                                                TextInput::make('hp_number')
                                                    ->label('HP Number')
                                                    ->default(fn ($record) => $record?->bankDetail?->hp_number ?? null),

                                                TextInput::make('email')
                                                    ->label('Email Address')
                                                    ->email()
                                                    ->default(fn ($record) => $record?->bankDetail?->email ?? null),
                                            ]),

                                        Tabs\Tab::make('Referral Address Details')
                                            ->schema([
                                                Textarea::make('referral_address')
                                                    ->label('Referral Address')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->referral_address ?? null),

                                                TextInput::make('postcode')
                                                    ->label('Post Code')
                                                    ->default(fn ($record) => $record?->bankDetail?->postcode ?? null),

                                                TextInput::make('city')
                                                    ->label('City')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->city ?? null),

                                                Select::make('state')
                                                    ->label('State')
                                                    ->options(function () {
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            // Map 3-letter country codes to full country names
                                                            return collect($countries)->mapWithKeys(function ($country) {
                                                                return [$country['Code'] => ucfirst(strtolower($country['State']))];
                                                            })->toArray();
                                                        }

                                                        return [];
                                                    })
                                                    ->dehydrateStateUsing(function ($state) {
                                                        // Convert the selected code to the full country name
                                                        $filePath = storage_path('app/public/json/StateCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            foreach ($countries as $country) {
                                                                if ($country['Code'] === $state) {
                                                                    return ucfirst(strtolower($country['State']));
                                                                }
                                                            }
                                                        }

                                                        return $state; // Fallback to the original state if mapping fails
                                                    })
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record->bankDetail->state ?? null)
                                                    ->searchable()
                                                    ->preload(),

                                                Select::make('country')
                                                    ->label('Country')
                                                    ->searchable()
                                                    ->required()
                                                    ->default('MYS')
                                                    ->options(function () {
                                                        $filePath = storage_path('app/public/json/CountryCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            // Map 3-letter country codes to full country names
                                                            return collect($countries)->mapWithKeys(function ($country) {
                                                                return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                            })->toArray();
                                                        }

                                                        return [];
                                                    })
                                                    ->dehydrateStateUsing(function ($state) {
                                                        // Convert the selected code to the full country name
                                                        $filePath = storage_path('app/public/json/CountryCodes.json');

                                                        if (file_exists($filePath)) {
                                                            $countriesContent = file_get_contents($filePath);
                                                            $countries = json_decode($countriesContent, true);

                                                            foreach ($countries as $country) {
                                                                if ($country['Code'] === $state) {
                                                                    return ucfirst(strtolower($country['Country'])); // Store the full country name
                                                                }
                                                            }
                                                        }

                                                        return $state; // Fallback to the original state if mapping fails
                                                    })
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state)),
                                            ]),

                                        Tabs\Tab::make('Referral Bank Details')
                                            ->schema([
                                                TextInput::make('referral_bank_name')
                                                    ->label('Referral Name')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->referral_name ?? $record?->bankDetail?->full_name ?? null),

                                                TextInput::make('beneficiary_name')
                                                    ->label('Beneficiary Name')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->beneficiary_name ?? $record?->bankDetail?->full_name ?? null),

                                                TextInput::make('bank_name')
                                                    ->label('Bank Name')
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                                    ->default(fn ($record) => $record?->bankDetail?->bank_name ?? null),

                                                TextInput::make('bank_account_no')
                                                    ->label('Bank Account Number')
                                                    ->default(fn ($record) => $record?->bankDetail?->bank_account_no ?? null),
                                            ]),
                                    ])
                                    ->columnSpanFull()
                            ])
                            ->action(function (Lead $lead, array $data) {
                                // Check if bank detail exists
                                $bank = $lead->bankDetail;

                                if ($bank) {
                                    // Update existing record
                                    $bank->update($data);

                                    Notification::make()
                                        ->title('Referral Details Updated')
                                        ->success()
                                        ->send();
                                } else {
                                    // Create new record
                                    $lead->bankDetail()->create($data);

                                    Notification::make()
                                        ->title('Referral Details Created')
                                        ->success()
                                        ->send();
                                }
                            }),
                        ])
                        ->schema([
                            View::make('components.bank-details')
                        ]),
                ]),
        ];
    }

    protected static function exportBankDetailsToExcel(Lead $lead)
    {
        return response()->streamDownload(function () use ($lead) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $bank = $lead->bankDetail;

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(40);

            // Set default alignment to left for all cells
            $sheet->getStyle('A1:B50')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

            // Referral Details Data
            $row = 1;
            // Section 1: Referral Details
            $sheet->setCellValue("A{$row}", 'REFERRAL DETAILS');
            $row++;
            $sheet->setCellValue("A{$row}", 'Referral Name');
            $sheet->setCellValue("B{$row}", $bank?->referral_name ?? $bank?->full_name ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Tax Identification Number');
            $sheet->setCellValue("B{$row}", $bank?->tin ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'HP Number');
            $sheet->setCellValue("B{$row}", $bank?->hp_number ?? $bank?->contact_no ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Email Address');
            $sheet->setCellValue("B{$row}", $bank?->email ?? '-');
            $row++;
            $row++;

            // Section 2: Referral Address Details
            $sheet->setCellValue("A{$row}", 'REFERRAL ADDRESS DETAILS');
            $row++;
            $sheet->setCellValue("A{$row}", 'Referral Address');
            $sheet->setCellValue("B{$row}", $bank?->referral_address ?? $bank?->address ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Post Code');
            $sheet->setCellValue("B{$row}", $bank?->postcode ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'City');
            $sheet->setCellValue("B{$row}", $bank?->city ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'State');
            $sheet->setCellValue("B{$row}", $bank?->state ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Country');
            $sheet->setCellValue("B{$row}", $bank?->country ?? '-');
            $row++;
            $row++;

            // Section 3: Referral Bank Details
            $sheet->setCellValue("A{$row}", 'REFERRAL BANK DETAILS');
            $row++;
            $sheet->setCellValue("A{$row}", 'Referral Name');
            $sheet->setCellValue("B{$row}", $bank?->referral_bank_name ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Beneficiary Name');
            $sheet->setCellValue("B{$row}", $bank?->beneficiary_name ?? $bank?->full_name ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Bank Name');
            $sheet->setCellValue("B{$row}", $bank?->bank_name ?? '-');
            $row++;
            $sheet->setCellValue("A{$row}", 'Bank Account Number');
            $sheet->setCellValue("B{$row}", $bank?->bank_account_no ?? '-');
            $row++;
            $row++;

            // Output the spreadsheet
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "referral_details_{$lead->id}.xlsx");
    }
}
