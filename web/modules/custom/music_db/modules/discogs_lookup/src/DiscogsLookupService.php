<?php

namespace Drupal\discogs_lookup;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class DiscogsLookupService
{
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  protected Config $config;
  public function __construct(ClientInterface $http_client, protected ConfigFactoryInterface $config_factory)
  {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('discogs_lookup.settings');

  }

  public function search(string $query, string $type = 'track', ?int $limit = NULL): array
  {
    $baseUrl = $this->config->get('api_uri') ?: 'https://api.discogs.com/database/search';
    $limit ??= (int)($this->config->get('max_hits') ?? 20);

    $token = $this->config->get('token');

    $headers = [
      'User-Agent' => 'MusicDBDrupal +https://tonlistar-skraning.ddev.site',
    ];

    $queryParams = [
      'q' => $query,
      'type' => $type,
      'per_page' => $limit,
      'page' => 1,
    ];

    if ($token) {
      $headers['Authorization'] = 'Discogs token=' . trim($token);
    }

    $response = $this->httpClient->get($baseUrl, [
      'headers' => $headers,
      'query' => $queryParams,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);

    return $data;
  }

  /**
   * Fetches artist information by Discogs ID.
   *
   * @param string 
   *
   * @return array
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getArtist(string $discogsId): array {
    $baseUrl = 'https://api.discogs.com/artists/' . $discogsId;

    $token = $this->config->get('token');

    $headers = [
      'User-Agent' => 'MusicDBDrupal +https://tonlistar-skraning.ddev.site',
    ];

    if ($token) {
      $headers['Authorization'] = 'Discogs token=' . trim($token);
    }

    $response = $this->httpClient->get($baseUrl, [
      'headers' => $headers,
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);

    return $data;
  }
}

