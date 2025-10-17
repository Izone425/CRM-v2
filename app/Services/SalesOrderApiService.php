<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SalesOrderApiService
{
    private string $baseUrl = 'http://ims.timeteccloud.com:16500/api';
    private string $username = 'hr_crm@timeteccloud.com';
    private string $password = 'ig3MFA81XTes';

    public function login(): ?string
    {
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['token'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getToken(): ?string
    {
        // Cache the token for 50 minutes (assuming 1-hour expiry)
        return Cache::remember('sales_order_api_token', 3000, function () {
            return $this->login();
        });
    }

    public function getSalesOrderStatus(string $soNo): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/get-sales-order-status', [
                    'so_no' => $soNo
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            // Handle specific error cases
            if ($response->status() === 500) {
                $errorBody = $response->body();

                // If it's a "property on null" error, the SO probably doesn't exist
                if (str_contains($errorBody, 'property') && str_contains($errorBody, 'null')) {
                    return [
                        'status' => null,
                        'so_no' => $soNo,
                        'message' => 'Sales Order not found in system'
                    ];
                }
            }

            // If unauthorized, clear cache and retry once
            if ($response->status() === 401) {
                Cache::forget('sales_order_api_token');
                $newToken = $this->login();

                if ($newToken) {
                    $retryResponse = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $newToken,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])
                        ->get($this->baseUrl . '/get-sales-order-status', [
                            'so_no' => $soNo
                        ]);

                    if ($retryResponse->successful()) {
                        return $retryResponse->json();
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
