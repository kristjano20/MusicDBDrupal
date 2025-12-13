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
   * Combines Spotify and Discogs results
   */
  public static function combineResults(array $spotify_results, array $discogs_results, string $type = 'artist', string $artist_name = ''): array {
    $combined_data = [];
    $seen_names = [];

    foreach ($spotify_results as $item) {
      $name = $item['name'] ?? '';
      if (!$name) {
        continue;
      }
      $normalized = self::normalizeName($name);
      $key = $normalized;

      if ($type === 'album') {
        $spotify_artists = $item['artist_names'] ?? [];
        $normalized_artists = array_map([self::class, 'normalizeName'], $spotify_artists);
        $key = $normalized . '|' . implode(',', $normalized_artists);
      }

      if (!isset($seen_names[$key])) {
        $entry = [
          'name' => $name,
          'spotify_id' => $item['id'] ?? '',
          'discogs_id' => '',
        ];
        if ($type === 'album' && !empty($spotify_artists)) {
          $entry['spotify_artists'] = $spotify_artists;
          $entry['artist_names'] = $spotify_artists;
        }
        $combined_data[] = $entry;
        $seen_names[$key] = TRUE;
      }
    }

    foreach ($discogs_results as $item) {
      $title = $item['title'] ?? '';
      if (!$title) {
        continue;
      }
      $normalized_title = self::normalizeName($title);
      $key = $normalized_title;

      if ($type === 'album') {
        $discogs_artists = $item['artist_names'] ?? [];
        $normalized_artists = !empty($discogs_artists) ? array_map([self::class, 'normalizeName'], $discogs_artists) : [];
        $key = $normalized_title . '|' . implode(',', $normalized_artists);

        $album_name = self::extractAlbumNameFromTitle($title);
        $normalized_album = self::normalizeName($album_name);
      } else {
        $album_name = $title;
        $normalized_album = $normalized_title;
      }

      $found = FALSE;
      foreach ($combined_data as &$existing) {
        $existing_normalized = self::normalizeName($existing['name']);

        if ($type === 'album') {
          $existing_artists = $existing['spotify_artists'] ?? $existing['artist_names'] ?? [];
          $existing_normalized_artists = !empty($existing_artists) ? array_map([self::class, 'normalizeName'], $existing_artists) : [];
          if ($existing_normalized === $normalized_album) {
            $artists_match = FALSE;
            if (!empty($normalized_artists) && !empty($existing_normalized_artists)) {
              $artists_match = count(array_intersect($normalized_artists, $existing_normalized_artists)) > 0;
            } else {
              $artists_match = TRUE;
            }

            if ($artists_match) {
              if (empty($existing['discogs_id']) || $existing['discogs_id'] === '') {
                $existing['discogs_id'] = (string)($item['id'] ?? '');
                if (empty($existing['artist_names']) && !empty($discogs_artists)) {
                  $existing['artist_names'] = $discogs_artists;
                }
              }
              $found = TRUE;
              break;
            }
          }
        } else {
          if ($existing_normalized === $normalized_title) {
            if (empty($existing['discogs_id'])) {
              $existing['discogs_id'] = $item['id'] ?? '';
            }
            $found = TRUE;
            break;
          }
        }
      }

      if (!$found && !isset($seen_names[$key])) {
        $display_name = $type === 'album' ? $album_name : $title;
        $entry = [
          'name' => $display_name,
          'spotify_id' => '',
          'discogs_id' => $item['id'] ?? '',
        ];
        if ($type === 'album' && !empty($discogs_artists)) {
          $entry['artist_names'] = $discogs_artists;
        }
        $combined_data[] = $entry;
        $seen_names[$key] = TRUE;
      }
    }

    return $combined_data;
  }

  /**
   * Extracts album name from Discogs title
   */
  protected static function extractAlbumNameFromTitle(string $title): string {
    if (preg_match('/^(.+?)\s*-\s*(.+)$/', $title, $matches)) {
      return trim($matches[2]);
    }
    return $title;
  }

}
