<?php

namespace App\Service\Ankorstore;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Ankorstore
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $versionApi = 'v1';

    public function __construct()
    {
        $this->connexionApi();
        $this->accessToken = $this->session();
    }

    private function connexionApi()
    {
        $this->clientId = ''; // ICI SE TROUVE LE CLIENT ID DE L'ENTREPRISE
        $this->clientSecret = ''; // ICI SE TROUVE LE CLIENT SECRET DE L'ENTREPRISE

        $this->client = new Client([
            'base_uri' => 'https://www.ankorstore.com',
            'timeout' => 5.0,
        ]);
    }

    private function session()
    {
        $response = $this->client->request('POST', '/oauth/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => '*'
            ],
        ]);

        $data = json_decode($response->getBody());
        return $data->access_token;
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

    public function request(string $method, string $endpoint, array $data = [], string $nextUrl = null): array|object
    {
        if (null === $nextUrl) {
            $url = "/api/{$this->versionApi}/$endpoint";
        } else {
            $url = $nextUrl;
        }

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Accept' => 'application/vnd.api+json',
                    'Authorization' => 'Bearer ' . $this->accessToken,
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
        if(is_object($body->data)) return $body;

        $nextUrl = $body->links->next ?? '';
        $data = array_merge($data, $body->data);

        if ('' !== $nextUrl) {
            usleep(10);
            return $this->request($method, $endpoint, $data, $nextUrl);
        } else {
            return $data;
        }
    }

    public function getUserInfo()
    {
        return $this->get('me');
    }

    public function getAllProducts(): array
    {
        return $this->get('products');
    }

    public function getProductsById(string $id, bool $includeVariant=true): object
    {
        $endpoint = 'products/'.$id;
        if($includeVariant) $endpoint .= '?include=productVariants';
        $res = $this->get($endpoint);
        if(!isset($res->included)) return $res->data;
        $res->data->included = $res->included;
        return $res->data;
    }

    public function getAllProductVariant(): array
    {
        return $this->get('product-variants');
    }

    public function getProductVariantById($id): object
    {
        return $this->get('product-variants/'.$id)->data;
    }

    public function getCatalogue(): array
    {
        $productList = $this->getAllProducts();
        foreach($productList as $i=>$product){
            $productList[$i] = $this->getProductsById($product->id);
        }        
        return $productList;
    }

    public function getAllOrders()
    {
        return $this->get('orders');
    }

    public function getMasterOrders()
    {
        return $this->get('master-orders');
    }
}