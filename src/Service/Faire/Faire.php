<?php

namespace App\Service\Faire;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Faire
{
    private $client;
    private $accessToken;
    private $applicationId;
    private $applicationSecret;
    private $versionApi = 'v2';

    public function __construct()
    {
        $this->applicationId = ''; // ICI SE TROUVE L'APPLICATION ID DE L'ENTREPRISE
        $this->applicationSecret = ''; // ICI SE TROUVE L'APPLICATION ID DE L'ENTREPRISE
        $this->accessToken = ''; // ICI SE TROUVE L'ACESS TOKEN FOURNIS PAR FAIRE

        $this->client = new Client([
            'base_uri' => 'https://www.faire.com',
            'timeout' => 5.0,
        ]);
    }

    public function get(string $endpoint): array|object
    {
        return $this->request('GET', $endpoint);
    }

    public function put(string $endpoint): array
    {
        return $this->request('PUT', $endpoint);
    }

    public function error($code)
    {
        $message = [
            400 => 'Requête incorrecte',
            401 => 'Erreur de connexion',
            403 => 'Accès refusé',
            404 => 'Ressource non trouvée',
            406 => 'Non acceptable',
            415 => 'Média non supporté',
            500 => 'Erreur interne du serveur',
            502 => 'Mauvaise passerelle',
            503 => 'Service indisponible',
            504 => 'Temps de réponse écoulé',
        ];

        return ['message' => $message[$code], 'code' => $code];
    }

    public function request(string $method, string $endpoint, array $data = []): array|object
    {
        $url = "/external-api/{$this->versionApi}/$endpoint";

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'X-FAIRE-APP-CREDENTIALS' => base64_encode($this->applicationId . ':' . $this->applicationSecret),
                    'X-FAIRE-OAUTH-ACCESS-TOKEN' => $this->accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            return $this->error($response->getStatusCode());
        }

        if (200 !== $response->getStatusCode()) {
            return $this->error($response->getStatusCode());
        }

        $body = json_decode($response->getBody());
        return $body;
    }

    public function getAllProducts(): array
    {
        return $this->get('products');
    }
}
