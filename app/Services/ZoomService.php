<?php

namespace App\Services;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;

class ZoomService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = new GenericProvider([
            'clientId' => env('ZOOM_CLIENT_ID'),
            'clientSecret' => env('ZOOM_CLIENT_SECRET'),
            'redirectUri' => env('ZOOM_REDIRECT_URI'),
            'urlAuthorize' => 'https://zoom.us/oauth/authorize',
            'urlAccessToken' => 'https://zoom.us/oauth/token',
            'urlResourceOwnerDetails' => 'https://api.zoom.us/v2/users/me',
        ]);
    }

    public function getAuthorizationUrl()
    {
        return $this->provider->getAuthorizationUrl();
    }

    public function getAccessToken($code)
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
    }

    public function createMeeting($accessToken, $data)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->post('users/me/meetings', [
            'json' => $data,
        ]);

        return json_decode($response->getBody(), true);
    }
}