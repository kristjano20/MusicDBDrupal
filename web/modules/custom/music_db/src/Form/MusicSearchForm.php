<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for searching music entities across multiple APIs.
 *
 * This form allows users to search for artists, albums, or songs using
 * Spotify and Discogs APIs. Results are combined and deduplicated.
 */
class MusicSearchForm extends FormBase {

  /**
   * The Spotify lookup service.
   *
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected SpotifyLookupService $spotifyLookup;

  /**
   * The Discogs lookup service.
   *
   * @var \Drupal\discogs_lookup\DiscogsLookupService
   */
  protected DiscogsLookupService $discogsLookup;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Constructs a MusicSearchForm.
   *
   * @param \Drupal\spotify_lookup\SpotifyLookupService $spotify_lookup
   *   The Spotify lookup service.
   * @param \Drupal\discogs_lookup\DiscogsLookupService $discogs_lookup
   *   The Discogs lookup service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(SpotifyLookupService $spotify_lookup, DiscogsLookupService $discogs_lookup, PrivateTempStoreFactory $temp_store_factory) {
    $this->spotifyLookup = $spotify_lookup;
    $this->discogsLookup = $discogs_lookup;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('spotify_lookup.search'),
      $container->get('discogs_lookup.search'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'music_db_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#required' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $form_state->getValue('query', ''),
    ];

    $form['entity_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => [
        'artist' => $this->t('Artist'),
        'album' => $this->t('Album'),
        'song' => $this->t('Song'),
      ],
      '#default_value' => $form_state->getValue('entity_type', 'artist'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    // Display results if form was submitted and artist is selected.
    if ($form_state->isSubmitted()) {
      $query = $form_state->getValue('query');
      $entity_type = $form_state->getValue('entity_type');

      if ($entity_type === 'artist' && !empty($query)) {
        $form['results'] = $this->buildArtistResults($query, $form_state);
      }
    }

    return $form;
  }

  /**
   * Builds the artist search results from Spotify and Discogs.
   *
   * Searches both APIs and combines results, merging entries with matching
   * artist names (case-insensitive).
   *
   * @param string $query
   *   The search query.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The results render array.
   */
  protected function buildArtistResults(string $query, FormStateInterface $form_state): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['music-db-search-results']],
    ];

    $artist_data = [];
    $seen_names = [];

    // Search Spotify.
    try {
      $spotify_data = $this->spotifyLookup->search($query, 'artist', 20);
      $spotify_artists = $spotify_data['artists']['items'] ?? [];

      foreach ($spotify_artists as $artist) {
        $name = $artist['name'] ?? '';
        $spotify_id = $artist['id'] ?? '';
        if (!$name) {
          continue;
        }

        // Normalize name for duplicate checking.
        $normalized_name = mb_strtolower(trim($name));
        if (!isset($seen_names[$normalized_name])) {
          $artist_data[] = [
            'name' => $name,
            'spotify_id' => $spotify_id,
            'discogs_id' => '',
          ];
          $seen_names[$normalized_name] = TRUE;
        }
      }
    }
    catch (\Throwable $e) {
    }

    // Search Discogs.
    try {
      $discogs_data = $this->discogsLookup->search($query, 'artist', 20);
      $discogs_results = $discogs_data['results'] ?? [];

      foreach ($discogs_results as $result) {
        $name = $result['title'] ?? '';
        $discogs_id = $result['id'] ?? '';
        if (!$name) {
          continue;
        }

        // Normalize name for duplicate checking.
        $normalized_name = mb_strtolower(trim($name));

        // Check if we already have this artist from Spotify.
        $found = FALSE;
        foreach ($artist_data as &$existing_artist) {
          if (mb_strtolower(trim($existing_artist['name'])) === $normalized_name) {
            // Update existing entry with Discogs ID.
            $existing_artist['discogs_id'] = $discogs_id;
            $found = TRUE;
            break;
          }
        }

        // If not found, add as new entry.
        if (!$found) {
          $artist_data[] = [
            'name' => $name,
            'spotify_id' => '',
            'discogs_id' => $discogs_id,
          ];
          $seen_names[$normalized_name] = TRUE;
        }
      }
    }
    catch (\Throwable $e) {
    }

    if (empty($artist_data)) {
      $build['no_results'] = [
        '#type' => 'markup',
        '#markup' => '<p><strong>' . $this->t('No artists found.') . '</strong></p>',
      ];
      return $build;
    }

    // Store all artist data in form state.
    $form_state->set('artist_data', $artist_data);

    // Add a fieldset to group the results nicely.
    $build['results_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Results (@count found)', ['@count' => count($artist_data)]),
      '#collapsible' => FALSE,
    ];

    // Build a table for better organization.
    $build['results_fieldset']['results_table'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Select'), 'style' => 'width: 50px; text-align: center;'],
        ['data' => $this->t('Artist Name'), 'style' => 'width: auto;'],
      ],
      '#empty' => $this->t('No results found.'),
    ];

    foreach ($artist_data as $index => $data) {
      $build['results_fieldset']['results_table'][$index]['select'] = [
        '#type' => 'radio',
        '#return_value' => json_encode([
          'name' => $data['name'],
          'spotify_id' => $data['spotify_id'] ?? '',
          'discogs_id' => $data['discogs_id'] ?? '',
        ]),
        '#attributes' => ['title' => $this->t('Select @name', ['@name' => $data['name']])],
        '#parents' => ['selected_artist'],
      ];

      $build['results_fieldset']['results_table'][$index]['name'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('@name', ['@name' => $data['name']]) . '</strong>',
      ];
    }

    // Add submit button to process selected artist.
    if (!empty($artist_data)) {
      $build['results_fieldset']['add_selected'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add Selected Artist'),
        '#name' => 'add_selected',
        '#submit' => ['::submitSelectedArtist'],
      ];

    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity_type = $form_state->getValue('entity_type');

    // Check if "Add Selected" button was clicked.
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#name']) && $triggering_element['#name'] === 'add_selected') {
      // Handled by submitSelectedArtist.
      return;
    }

    // For artist searches, rebuild the form to show results.
    if ($entity_type === 'artist') {
      $form_state->setRebuild();
      return;
    }

    // For other entity types, redirect to their add forms.
    $query = $form_state->getValue('query');
    $routes = [
      'album' => 'entity.album.add_form',
      'song' => 'entity.song.add_form',
    ];

    $route_name = $routes[$entity_type] ?? 'entity.artist.add_form';

    $url = Url::fromRoute($route_name, [], [
      'query' => ['q' => $query],
    ]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * Submit handler for adding selected artist.
   */
  public function submitSelectedArtist(array &$form, FormStateInterface $form_state): void {
    // Get the selected radio button value.
    $selected_value = $form_state->getValue('selected_artist');

    if (empty($selected_value)) {
      $this->messenger()->addWarning($this->t('Please select an artist.'));
      $form_state->setRebuild();
      return;
    }

    // Decode the JSON data stored in the radio button return value.
    $selected_artist = json_decode($selected_value, TRUE);

    if (!$selected_artist || !isset($selected_artist['name'])) {
      $this->messenger()->addError($this->t('Invalid artist data.'));
      $form_state->setRebuild();
      return;
    }

    // Store the selected artist data in tempstore for use in the next form.
    $tempstore = $this->tempStoreFactory->get('music_db');
    $tempstore->set('selected_artist', [
      'name' => $selected_artist['name'],
      'spotify_id' => $selected_artist['spotify_id'] ?? '',
      'discogs_id' => $selected_artist['discogs_id'] ?? '',
    ]);

    // Redirect to ArtistDataForm with the IDs as route parameters.
    $spotify_id = $selected_artist['spotify_id'] ?? '';
    $discogs_id = $selected_artist['discogs_id'] ?? '';

    // Use 'none' as placeholder for empty IDs (route requires both parameters).
    // ArtistDataForm should handle 'none' or empty values gracefully.
    $spotify_id = !empty($spotify_id) ? $spotify_id : 'none';
    $discogs_id = !empty($discogs_id) ? $discogs_id : 'none';

    $form_state->setRedirect('music_db.data_select_artist', [
      'spotify_id' => $spotify_id,
      'discogs_id' => $discogs_id,
    ]);
  }

}
