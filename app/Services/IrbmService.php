<?php

namespace App\Services;

use App\Models\IrbmToken;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use Klsheng\Myinvois\MyInvoisClient;
use Illuminate\Support\Facades\Log;
use Klsheng\Myinvois\Ubl\Constant\ClassificationCodes;
use Klsheng\Myinvois\Ubl\Constant\CountryCodes;
use Klsheng\Myinvois\Ubl\Constant\MSICCodes;
use Klsheng\Myinvois\Ubl\Constant\StateCodes;
use Exception;
use Illuminate\Database\Eloquent\Model;

class IrbmService
{
    private $client;
    public ?Carbon $tokenExpiryDateTime = null;
    private ?Model $companyInfo = null;
    private $accessToken = null;

    public function __construct()
    {
        $this->companyInfo = IrbmToken::first();
        Log::channel('irbm_log')->info('IrbmService initialized with company info: ' . json_encode($this->companyInfo));
        $productionMode = $this->companyInfo->production_mode;
        $this->client = new MyInvoisClient(clientId: $this->companyInfo->client_id, clientSecret: $this->companyInfo->client_secret, prodMode: $productionMode);
        $this->client->login();
        $this->checkAccessToken();
        $this->client->setAccessToken($this->accessToken);
    }

    private function checkAccessToken(): void
    {
        try {
            if (!$this->companyInfo->token) {
                $this->accessToken = $this->client->getAccessToken();
                Log::channel('irbm_log')->info('New access token retrieved(1): ' . $this->client->getAccessToken());
                $this->companyInfo->token = $this->accessToken;
                $this->companyInfo->save();
            } else {
                $this->accessToken = $this->companyInfo->token;
                /**
                 * if the token has expired (60 mins from last get)
                 */
                if ($this->isTokenExpired()) {
                    Log::channel('irbm_log')->info('Access token expired, retrieving new token.');
                    $this->accessToken = $this->client->getAccessToken();
                    Log::channel('irbm_log')->info('New access token retrieved(2): ' . $this->accessToken);
                    $this->companyInfo->token = $this->accessToken;
                    $this->companyInfo->save();
                } else {
                    Log::channel('irbm_log')->info('Using existing access token: ' . $this->companyInfo->token);
                    $this->accessToken = $this->companyInfo->token;
                }
            }
        } catch (Exception $e) {
            Log::channel('irbm_log')->error('Error retrieving access token: ' . $e->getMessage());
            throw $e;
        }
    }

    public function validateTaxPayerTin(string $tin, string $idType, string $idValue): bool
    {
        Log::channel('irbm_log')->info("Validating TIN: '{$tin}' with ID Type: '{$idType}' and ID Value: '{$idValue}'");
        /**
         * MyInvois returns success if validation passes, throws exception if fails
         */
        try {
            $response = $this->client->validateTaxPayerTin($tin, $idType, $idValue);
            Log::channel('irbm_log')->info("Response Status Code: {$this->client->statusCode}");
            Log::channel('irbm_log')->info("TIN Validation Response: " . json_encode($response));

            // If we get here without exception, validation succeeded
            return true;

        } catch (\Exception $ex) {
            $exception = json_decode($ex->getMessage(), true);
            Log::channel('irbm_log')->error("TIN Validation Exception: " . json_encode($exception));

            if ($exception && isset($exception['status']) && $exception['status'] !== 200) {
                Log::channel('irbm_log')->error("TIN: {$tin} validation failed - {$exception['title']} (status code: {$exception['status']})");
                Log::channel('irbm_log')->error("TIN validation input: tin => '{$tin}', id_type => '{$idType}', id_value => '{$idValue}'");
            }

            return false;
        }
    }

    public function searchTaxPayerTin(string $name = '', string $idType = 'BRN', string $idValue = ''): string
    {
        Log::channel('irbm_log')->info("Searching TIN for Name: '{$name}', ID Type: '{$idType}', ID Value: '{$idValue}'");
        /**
         * MyInvois returns TIN if found, throws exception if not
         */
        try {
            $response = $this->client->searchTaxPayerTin($name, $idType, $idValue);
            Log::channel('irbm_log')->info("Response Status Code: {$this->client->statusCode}");
            // Log the response for debugging
            Log::channel('irbm_log')->info("TIN Search Response: " . json_encode($response));

            // Check if response contains TIN
            if (is_array($response) && isset($response['tin'])) {
                Log::channel('irbm_log')->info("TIN found: {$response['tin']}");
                return $response['tin'];
            }

            // If response is a string (direct TIN), return it
            if (is_string($response) && !empty($response)) {
                Log::channel('irbm_log')->info("TIN found (string): {$response}");
                return $response;
            }

            Log::channel('irbm_log')->warning("TIN search returned unexpected format: " . json_encode($response));
            return '';

        } catch (\Exception $ex) {
            $exceptionMessage = $ex->getMessage();
            $exception = json_decode($exceptionMessage, true);

            Log::channel('irbm_log')->error("TIN Search Exception - Raw Message: " . $exceptionMessage);
            Log::channel('irbm_log')->error("TIN Search Exception - Decoded: " . json_encode($exception));
            Log::channel('irbm_log')->error("TIN Search Exception - Code: " . $ex->getCode());

            if ($exception && isset($exception['statusCode'])) {
                if ($exception['statusCode'] !== 200) {
                    $errorMsg = $exception['message'] ?? $exception['error'] ?? 'Unknown error';
                    Log::channel('irbm_log')->error("TIN search failed: {$errorMsg} (status code: {$exception['statusCode']})");
                }
            } else {
                // Not a JSON response, log the raw error
                Log::channel('irbm_log')->error("TIN search error: {$exceptionMessage}");
            }

            Log::channel('irbm_log')->error("TIN search input: name => '{$name}', id_type => '{$idType}', id_value => '{$idValue}'");

            return '';
        }
    }

    public static function getClassificationCodes(?string $code = null): array | string
    {
        try {
            if ($code) {
                return ClassificationCodes::getDescription($code);
            }

            return ClassificationCodes::getItems();
        } catch (Exception $ex) {
            Log::channel('irbm_log')->error('Error retrieving classification codes: ' . $ex->getMessage());
            return [];
        }

        return [];
    }

    public static function getMSICCodes(?string $code = null): array | string
    {
        try {
            if ($code) {
                return MSICCodes::getDescription($code);
            }

            return MSICCodes::getItems();
        } catch (Exception $ex) {
            Log::channel('irbm_log')->error('Error retrieving MSIC codes: ' . $ex->getMessage());
            return [];
        }

        return [];
    }

    public static function getStateCodes(): ?array
    {
        try {
            return StateCodes::getItems();
        } catch (Exception $ex) {
            Log::channel('irbm_log')->error('Error retrieving state codes: ' . $ex->getMessage());
            return [];
        }

        return [];
    }

    public static function getCountryCodes(): ?array
    {
        try {
            return CountryCodes::getItems();
        } catch (Exception $ex) {
            Log::channel('irbm_log')->error('Error retrieving country codes: ' . $ex->getMessage());
            return [];
        }

        return [];
    }

    public function isTokenExpired(): bool
    {
        Log::channel('irbm_log')->info('Checking if token is expired...');
        // if (!$this->accessToken) {
        //     return true; // Token does not exist, consider it expired
        // }

        $token = $this->accessToken;
        Log::channel('irbm_log')->info('Access token: ' . $this->accessToken);
        if (!$token) {
            Log::channel('irbm_log')->info('Access token is empty.');
            return true; // Token is empty, consider it expired
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            //throw new Exception('Invalid token format');
            Log::channel('irbm_log')->info('Invalid token format.');
            return true;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (!$payload || !isset($payload['exp'])) {
            //throw new Exception('No expiration time found in token payload');
            Log::channel('irbm_log')->info('No expiration time found in token payload.');
            return true;
        }

        // $this->tokenExpiryDateTime = (new DateTime())->setTimestamp($payload['exp']);
        $this->tokenExpiryDateTime = Carbon::createFromTimestamp($payload['exp']);
        Log::channel('irbm_log')->info('Token expiry time: ' . $this->tokenExpiryDateTime->toDateTimeString());
        // Check if the token is expired
        if ($this->tokenExpiryDateTime > now()) {
            return false;
        }

        return true;
    }
}
