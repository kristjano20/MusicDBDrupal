<?php

namespace Drupal\spotify_lookup;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class SpotifyLookupService {
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  protected Config $config;

  protected ?string $accessToken = NULL;
  public function __construct(ClientInterface $http_client, protected ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('spotify_lookup.settings');

  }


  public function search(string $query, string $type = 'track', ?int $limit = NULL): array {
    $token = $this->getAccessToken();

    $baseUrl = $this->config->get('api_uri') ?: 'https://api.spotify.com/v1';
    $limit ??= (int) ($this->config->get('max_hits') ?? 20);

    $response = $this->httpClient->get(
      $baseUrl . '/search',
      [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
        ],
        'query' => [
          'q' => $query,
          'type' => $type,
          'limit' => $limit,
        ],
      ]
    );

    $data = json_decode($response->getBody()->getContents(), TRUE);

    return $data;
  }
  protected function getAccessToken(): string {
    if ($this->accessToken) {
      return $this->accessToken;
    }

    $clientId = $this->config->get('client_id');
    $clientSecret = $this->config->get('client_secret');

    $response = $this->httpClient->post(
      'https://accounts.spotify.com/api/token',
      [
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        ],
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]
    );

    $data = json_decode($response->getBody()->getContents(), TRUE);

    $this->accessToken = $data['access_token'];

    return $this->accessToken;
  }
}
