<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Oauth2;
use RuntimeException;

final class GoogleAuthService
{
    private Client $client;

    public function __construct(array $config)
    {
        $this->client = new Client();
        $this->client->setClientId($config['client_id']);
        $this->client->setClientSecret($config['client_secret']);
        $this->client->setRedirectUri($config['redirect_uri']);
        $this->client->addScope('email');
        $this->client->addScope('profile');
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException('Erro na autenticação com Google: ' . $token['error_description']);
        }

        $this->client->setAccessToken($token);
        $oauth2 = new Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        return [
            'google_id' => $userInfo->id,
            'email' => $userInfo->email,
            'name' => $userInfo->name,
            'picture' => $userInfo->picture,
        ];
    }
}
