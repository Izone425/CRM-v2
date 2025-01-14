<?php

namespace App\Services;

use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Generated\Models\OnlineMeeting;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

class MicrosoftTeamsServiceV2
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->tenantId = env('MICROSOFT_TENANT_ID');
        $this->clientId = env('MICROSOFT_CLIENT_ID');
        $this->clientSecret = env('MICROSOFT_CLIENT_SECRET');
    }

    public function createOnlineMeeting()
    {
        try {
        $scopes = ['https://graph.microsoft.com/.default'];

        // Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext
        $tokenContext = new ClientCredentialContext(
            $this->tenantId,
            $this->clientId,
            $this->clientSecret);

        $graphClient = new GraphServiceClient($tokenContext, $scopes);

        $requestBody = new OnlineMeeting();
        $requestBody->setStartDateTime(new \DateTime('2024-12-23T14:30:34.2444915-08:00'));
        $requestBody->setEndDateTime(new \DateTime('2024-12-23T15:00:34.2464912-08:00'));
        $requestBody->setSubject('User Token Meeting');

        $result = $graphClient->me()->onlineMeetings()->post($requestBody)->wait();
        // $result = $graphClient->me()->get()->wait();
        dd($result);
        } catch (ODataError $e) {
            echo "Error Code: " . $e->getCode();
            echo "Error Message: " . $e->getMessage();
        }
    }

    public function start()
    {
        try {
        // The client credentials flow requires that you request the
        // /.default scope, and pre-configure your permissions on the
        // app registration in Azure. An administrator must grant consent
        // to those permissions beforehand.
        $scopes = ['https://graph.microsoft.com/.default'];

        // Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext
        $tokenContext = new ClientCredentialContext(
            $this->tenantId,
            $this->clientId,
            $this->clientSecret);

        $graphClient = new GraphServiceClient($tokenContext, $scopes);

        $result = $graphClient->me()->calendars()->get()->wait();

        dd($result);
        // $query = new MessagesRequestBuilderGetQueryParameters(
        //     select: ['subject', 'sender'],
        //     filter: 'subject eq \'Hello world\''
        // );

        // // Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration
        // $config = new MessagesRequestBuilderGetRequestConfiguration(
        //     queryParameters: $query);

        // /** @var Models\MessageCollectionResponse $messages */
        // $messages = $graphClient->me()
        //     ->messages()
        //     ->get($config)
        //     ->wait();

        return $messages;
        } catch( \Exception $ex) {
            echo $ex->getMessage();
        }
    }
}
