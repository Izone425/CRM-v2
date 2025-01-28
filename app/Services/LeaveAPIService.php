<?php

namespace App\Services;

use SoapClient;
use SoapHeader;
use SoapFault;


class LeaveAPIService{

    private $wsdl;
    private $client;
    private $username;
    private $password;
    private $token;

    public function __construct($wsdl, $username, $password)
    {
        $this->wsdl = $wsdl;
        $this->username = $username;
        $this->password = $password;

        // Initialize SOAP client
        $this->initializeClient();
    }

    private function initializeClient()
    {
        $this->client = new SoapClient($this->wsdl, [
            'trace' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);

        // Attach the initial security header
        $this->setSoapHeader();
    }

    private function setSoapHeader()
    {
        // Generate a new security header with the token
        $headerBody = [
            'WSUsername' => $this->username,
            'WSPassword' => $this->password,
            'SecurityToken' => $this->token ?? $this->requestNewToken(),
        ];
        $soapHeader = new SoapHeader("http://tempuri.org/","WebServiceSoapHeader",$headerBody);
        $this->client->__setSoapHeaders($soapHeader);
    }

    private function requestNewToken()
    {
        $tempClient = new SoapClient($this->wsdl);

        $params = ['Username'=>$this->username,'Password'=>$this->password];

        $response = $tempClient->WebServiceLogin($params);
        return json_decode($response->WebServiceLoginResult)->Result->SecurityToken;
    }

    public function getClient(){
        return $this->client;
    }

    private function isTokenExpired(SoapFault $e)
    {
        // Check the error message or code to identify if it's a token expiration
        return strpos($e->getMessage(), 'Token expired') !== false;
    }

    
}