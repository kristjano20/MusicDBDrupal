<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete controller for spotify search
 */
class SpotifyAutocompleteController extends ControllerBase {
  protected SpotifyLookupService $spotifyLookup;

  /**
   * Constructs a SpotifyAutocompleteController.
   * @param \Drupal\spotify_lookup\SpotifyLookupService $spotify_lookup
   */
  public function __construct(SpotifyLookupService $spotify_lookup) {
    $this->spotifyLookup = $spotify_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('spotify_lookup.search')
    );
  }

  /**
   * Returns Spotify matches for autocomplete.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $type
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleAutocomplete(Request $request, string $type = 'artist'): JsonResponse {
    $input = trim((string) $request->query->get('q'));
    $matches = [];

    if (mb_strlen($input) < 2) {
      return new JsonResponse($matches);
    }
    $type_map = [
      'album' => ['search_type' => 'album', 'data_path' => 'albums.items', 'name_key' => 'name'],
      'song' => ['search_type' => 'track', 'data_path' => 'tracks.items', 'name_key' => 'name'],
      'artist' => ['search_type' => 'artist', 'data_path' => 'artists.items', 'name_key' => 'name'],
    ];

    $config = $type_map[$type] ?? $type_map['artist'];
    $search_type = $config['search_type'];
    $data_path = explode('.', $config['data_path']);
    $name_key = $config['name_key'];

    try {
      $data = $this->spotifyLookup->search($input, $search_type, 8);
      $items = $data;
      foreach ($data_path as $key) {
        $items = $items[$key] ?? [];
      }

      foreach ($items as $item) {
        $name = $item[$name_key] ?? '';
        if (!$name) {
          continue;
        }

        $matches[] = [
          'value' => $name,
          'label' => $name,
          'spotify_id' => $item['id'] ?? '',
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('music_db')->error('Spotify @type autocomplete failed: @message', [
        '@type' => $type,
        '@message' => $e->getMessage(),
      ]);
    }

    return new JsonResponse($matches);
  }

}

