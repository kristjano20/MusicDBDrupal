<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\spotify_lookup\SpotifyLookupService;
use Drupal\discogs_lookup\DiscogsLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * controller to lookup artist ID from opposite API.
 */
class ArtistIdLookupController extends ControllerBase {

  /**
   * Spotify lookup service.
   */
  protected SpotifyLookupService $spotifyLookup;

  /**
   * Discogs lookup servcie.
   */
  protected DiscogsLookupService $discogsLookup;

  public function __construct(SpotifyLookupService $spotify_lookup, DiscogsLookupService $discogs_lookup) {
    $this->spotifyLookup = $spotify_lookup;
    $this->discogsLookup = $discogs_lookup;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('spotify_lookup.search'),
      $container->get('discogs_lookup.search')
    );
  }

  /**
   * Looks up artist ID from the opposite api.
   */
  public function lookupId(Request $request): JsonResponse {
    $artist_name = trim((string) $request->query->get('name'));
    $provider = trim((string) $request->query->get('provider'));

    if (empty($artist_name) || empty($provider)) {
      return new JsonResponse(['id' => null], 400);
    }

    $id = null;

    try {
      if ($provider === 'spotify') {
        $data = $this->discogsLookup->search($artist_name, 'artist', 1);
        $results = $data['results'] ?? [];
        if (!empty($results) && isset($results[0]['id'])) {
          $id = (string) $results[0]['id'];
        }
      }
      elseif ($provider === 'discogs') {
        $data = $this->spotifyLookup->search($artist_name, 'artist', 1);
        $artists = $data['artists']['items'] ?? [];
        if (!empty($artists) && isset($artists[0]['id'])) {
          $id = $artists[0]['id'];
        }
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('music_db')->error('Artist ID lookup failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['id' => null], 500);
    }

    return new JsonResponse(['id' => $id]);
  }

}

