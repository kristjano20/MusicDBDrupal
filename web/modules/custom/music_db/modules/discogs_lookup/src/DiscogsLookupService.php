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
   * Searches for artists by name.
   *
   * @param string $query
   *   The search query.
   * @param int|null $limit
   *   Optional limit for number of results.
   *
   * @return array
   *   Array containing search results.
   */
  public function searchArtists(string $query, ?int $limit = NULL): array {
    return $this->search($query, 'artist', $limit);
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


  public function searchAlbums(string $query, ?int $limit = NULL): array {
    $baseUrl = $this->config->get('api_uri') ?: 'https://api.discogs.com/database/search';
    $limit ??= (int)($this->config->get('max_hits') ?? 20);

    $token = $this->config->get('token');

    $headers = [
      'User-Agent' => 'MusicDBDrupal +https://tonlistar-skraning.ddev.site',
    ];

    $queryParams = [
      'q' => $query,
      'type' => 'master',
      'format' => 'Album',
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
   * Fetches album (master release) information by Discogs ID.
   *
   * @param string $discogsId
   *   The Discogs master ID.
   *
   * @return array
   *   Array containing master release data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAlbum(string $discogsId): array {
    $baseUrl = 'https://api.discogs.com/masters/' . $discogsId;

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

