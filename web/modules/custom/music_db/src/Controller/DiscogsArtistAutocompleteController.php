<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\discogs_lookup\DiscogsLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete controller for Discogs artist search.
 */
class DiscogsArtistAutocompleteController extends ControllerBase {

  /**
   * Discog lookup client.
   */
  protected DiscogsLookupService $discogsLookup;

  public function __construct(DiscogsLookupService $discogs_lookup) {
    $this->discogsLookup = $discogs_lookup;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('discogs_lookup.search')
    );
  }

  /**
   * Returns Discogs artist matches for autocomplete.
   */
  public function handleAutocomplete(Request $request): JsonResponse {
    $input = trim((string) $request->query->get('q'));
    $matches = [];

    if (mb_strlen($input) < 2) {
      return new JsonResponse($matches);
    }

    try {
      $data = $this->discogsLookup->search($input, 'artist', 8);
      $results = $data['results'] ?? [];

      foreach ($results as $result) {
        $name = $result['title'] ?? '';
        $id = $result['id'] ?? '';
        if (!$name) {
          continue;
        }

        $matches[] = [
          'value' => $name,
          'label' => $name,
          'discogs_id' => $id,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('music_db')->error('Discogs autocomplete failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return new JsonResponse($matches);
  }

}

