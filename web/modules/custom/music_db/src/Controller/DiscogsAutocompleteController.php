<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\discogs_lookup\DiscogsLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete controller for Discogs search
 */
class DiscogsAutocompleteController extends ControllerBase {
  protected DiscogsLookupService $discogsLookup;

  /**
   * Constructs a DiscogsAutocompleteController
   *
   * @param \Drupal\discogs_lookup\DiscogsLookupService $discogs_lookup
   */
  public function __construct(DiscogsLookupService $discogs_lookup) {
    $this->discogsLookup = $discogs_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('discogs_lookup.search')
    );
  }

  /**
   * Returns discogs matches for autocomplete.
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

    $search_type = match ($type) {
      'album', 'song' => 'release',
      default => 'artist',
    };

    try {
      $data = $this->discogsLookup->search($input, $search_type, 8);
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
      $this->getLogger('music_db')->error('Discogs @type autocomplete failed: @message', [
        '@type' => $type,
        '@message' => $e->getMessage(),
      ]);
    }

    return new JsonResponse($matches);
  }

}

