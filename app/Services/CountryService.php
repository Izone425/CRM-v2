<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CountryService
{
    private string $apiUrl;

    // Timezone mapping for common countries
    private array $timezoneMap = [
        'Malaysia' => 'Asia/Kuala_Lumpur',
        'Singapore' => 'Asia/Singapore',
        'Indonesia' => 'Asia/Jakarta',
        'Thailand' => 'Asia/Bangkok',
        'Philippines' => 'Asia/Manila',
        'United States' => 'America/New_York',
        'United Kingdom' => 'Europe/London',
        'Australia' => 'Australia/Sydney',
    ];

    public function __construct()
    {
        $this->apiUrl = 'https://int-general-hr-test.timeteccloud.com/api/general/countries';
    }

    public function getCountries(): array
    {
        return Cache::remember('crm_countries', 3600, function () {
            try {
                return $this->fetchCountriesFromApi();
            } catch (\Exception $e) {
                Log::error('Failed to fetch countries from API', [
                    'error' => $e->getMessage()
                ]);
                return $this->getFallbackCountries();
            }
        });
    }

    private function fetchCountriesFromApi(): array
    {
        Log::info('Fetching countries from REST API', [
            'url' => $this->apiUrl
        ]);

        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->get($this->apiUrl);

        if (!$response->successful()) {
            throw new \Exception("API Error: HTTP {$response->status()}");
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new \Exception("Invalid API response format");
        }

        $countries = [];
        foreach ($data as $country) {
            if ($country['isActive']) {
                $countries[] = [
                    'id' => $country['id'],
                    'name' => $country['countryName'],
                    'phone_code' => $country['phoneCode'],
                    'iso2' => $country['iso2Code'],
                    'iso3' => $country['iso3Code'],
                    'currency_code' => $country['currencyCode'],
                    'currency_symbol' => $country['currencySymbol'],
                    'nationality' => $country['nationality'] ?? '',
                    'timezone' => $this->timezoneMap[$country['countryName']] ?? 'UTC',
                ];
            }
        }

        Log::info('Successfully fetched countries', [
            'count' => count($countries)
        ]);

        return $countries;
    }

    private function getFallbackCountries(): array
    {
        Log::warning('Using fallback countries list');

        return [
            ['id' => 1, 'name' => 'Afghanistan', 'phone_code' => '+93', 'iso2' => 'AF', 'iso3' => 'AFG', 'currency_code' => 'AFN', 'currency_symbol' => '؋', 'nationality' => 'Afghan', 'timezone' => 'Asia/Kabul'],
            ['id' => 132, 'name' => 'Malaysia', 'phone_code' => '+60', 'iso2' => 'MY', 'iso3' => 'MYS', 'currency_code' => 'MYR', 'currency_symbol' => 'RM', 'nationality' => 'Malaysian', 'timezone' => 'Asia/Kuala_Lumpur'],
            ['id' => 133, 'name' => 'Singapore', 'phone_code' => '+65', 'iso2' => 'SG', 'iso3' => 'SGP', 'currency_code' => 'SGD', 'currency_symbol' => 'S$', 'nationality' => 'Singaporean', 'timezone' => 'Asia/Singapore'],
            ['id' => 101, 'name' => 'Indonesia', 'phone_code' => '+62', 'iso2' => 'ID', 'iso3' => 'IDN', 'currency_code' => 'IDR', 'currency_symbol' => 'Rp', 'nationality' => 'Indonesian', 'timezone' => 'Asia/Jakarta'],
            ['id' => 214, 'name' => 'Thailand', 'phone_code' => '+66', 'iso2' => 'TH', 'iso3' => 'THA', 'currency_code' => 'THB', 'currency_symbol' => '฿', 'nationality' => 'Thai', 'timezone' => 'Asia/Bangkok'],
            ['id' => 170, 'name' => 'Philippines', 'phone_code' => '+63', 'iso2' => 'PH', 'iso3' => 'PHL', 'currency_code' => 'PHP', 'currency_symbol' => '₱', 'nationality' => 'Filipino', 'timezone' => 'Asia/Manila'],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('crm_countries');
    }

    public function getCountryById(int $id): ?array
    {
        $countries = $this->getCountries();

        foreach ($countries as $country) {
            if ($country['id'] === $id) {
                return $country;
            }
        }

        return null;
    }
}
