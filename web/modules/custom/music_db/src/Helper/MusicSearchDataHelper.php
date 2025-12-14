<?php

namespace Drupal\music_db\Helper;

/**
 * Helper class for filtering search data.
 */
class MusicSearchDataHelper {
  protected static function normalizeName(string $name): string {
    return mb_strtolower(trim($name));
  }

  /**
   * Combines Spotify and Discogs results (only used for artist/song, not album).
   */
  public static function combineResults(array $spotify_results, array $discogs_results): array {
    $combined_data = [];
    $seen_names = [];

    foreach ($spotify_results as $item) {
      $name = $item['name'] ?? '';
      if (!$name) {
        continue;
      }
      $normalized = self::normalizeName($name);
      if (!isset($seen_names[$normalized])) {
        $combined_data[] = ['name' => $name, 'spotify_id' => $item['id'] ?? '', 'discogs_id' => ''];
        $seen_names[$normalized] = TRUE;
      }
    }

    foreach ($discogs_results as $item) {
      $title = $item['title'] ?? '';
      if (!$title) {
        continue;
      }
      $normalized_title = self::normalizeName($title);
      $found = FALSE;
      foreach ($combined_data as &$existing) {
        if (self::normalizeName($existing['name']) === $normalized_title) {
          if (empty($existing['discogs_id'])) {
            $existing['discogs_id'] = $item['id'] ?? '';
          }
          $found = TRUE;
          break;
        }
      }
      if (!$found && !isset($seen_names[$normalized_title])) {
        $combined_data[] = ['name' => $title, 'spotify_id' => '', 'discogs_id' => $item['id'] ?? ''];
        $seen_names[$normalized_title] = TRUE;
      }
    }

    return $combined_data;
  }

}
