<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete controller for Spotify artist search.
 */
class SpotifyArtistAutocompleteController extends ControllerBase {

  /**
   * Spotify lookup client.
   */
  protected SpotifyLookupService $spotifyLookup;

  public function __construct(SpotifyLookupService $spotify_lookup) {
    $this->spotifyLookup = $spotify_lookup;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('spotify_lookup.search')
    );
  }

  /**
   * Returns Spotify artist matches for autocomplete.
   */
  public function handleAutocomplete(Request $request): JsonResponse {
    $input = trim((string) $request->query->get('q'));
    $matches = [];

    if (mb_strlen($input) < 2) {
      return new JsonResponse($matches);
    }

    try {
      $data = $this->spotifyLookup->search($input, 'artist', 8);
      $artists = $data['artists']['items'] ?? [];

      foreach ($artists as $artist) {
        $name = $artist['name'] ?? '';
        if (!$name) {
          continue;
        }

        $matches[] = [
          'value' => $name,
          'label' => $name,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('music_db')->error('Spotify autocomplete failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return new JsonResponse($matches);
  }

}

