<?php

namespace Drupal\spotify_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class SpotifyLookupService {
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected ?string $accessToken = NULL;
  public function __construct(ClientInterface $http_client, protected ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }
  public function search(string $query, string $type = 'track', int $limit = 5): array {
    $token = $this->getAccessToken();

    $response = $this->httpClient->get(
      'https://api.spotify.com/v1/search',
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

    $config = $this->configFactory->get('spotify_lookup.settings');

    $clientId = $config->get('client_id');
    $clientSecret = $config->get('client_secret');

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
