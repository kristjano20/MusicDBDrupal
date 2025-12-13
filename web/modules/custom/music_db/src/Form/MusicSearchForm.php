<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\music_db\Helper\MusicSearchDataHelper;
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
   * Discogs lookup service.
   * @var \Drupal\discogs_lookup\DiscogsLookupService
   */
  protected DiscogsLookupService $discogsLookup;

  /**
   *Ttempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Constructs a MusicSearchForm.
   * @param \Drupal\spotify_lookup\SpotifyLookupService $spotify_lookup
   * @param \Drupal\discogs_lookup\DiscogsLookupService $discogs_lookup
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
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

    if ($form_state->isSubmitted()) {
      $query = $form_state->getValue('query');
      $entity_type = $form_state->getValue('entity_type');
      if (in_array($entity_type, ['artist', 'album', 'song']) && !empty($query)) {
        $form['results'] = $this->buildResults($query, $entity_type, $form_state);
      }
    }

    return $form;
  }

  /**
   * Builds search results from Spotify and Discogs.
   */
  protected function buildResults(string $query, string $entity_type, FormStateInterface $form_state): array {
    $build = ['#type' => 'container', '#attributes' => ['class' => ['music-db-search-results']]];
    
    $spotify_methods = ['artist' => 'searchArtists', 'album' => 'searchAlbums', 'song' => 'searchSongs'];
    $discogs_methods = ['artist' => 'searchArtists', 'album' => 'searchAlbums'];
    $spotify_keys = ['artist' => 'artists', 'album' => 'albums', 'song' => 'tracks'];
    
    $spotify_method = $spotify_methods[$entity_type] ?? 'searchArtists';
    $spotify_key = $spotify_keys[$entity_type] ?? 'artists';
    $discogs_method = $discogs_methods[$entity_type] ?? NULL;
    
    $spotify_items = [];
    try {
      $spotify_data = $this->spotifyLookup->$spotify_method($query, 20);
      $items = $spotify_data[$spotify_key]['items'] ?? [];
      foreach ($items as $item) {
        if (!empty($item['name']) && !empty($item['id'])) {
          $spotify_items[] = ['name' => $item['name'], 'id' => $item['id']];
        }
      }
    }
    catch (\Throwable $e) {
    }

    $discogs_items = [];
    if ($discogs_method) {
      try {
        $discogs_data = $this->discogsLookup->$discogs_method($query, 20);
        foreach ($discogs_data['results'] ?? [] as $result) {
          if (!empty($result['title']) && !empty($result['id'])) {
            $discogs_items[] = ['title' => $result['title'], 'id' => $result['id']];
          }
        }
      }
      catch (\Throwable $e) {
      }
    }

    $data = MusicSearchDataHelper::combineResults($spotify_items, $discogs_items, $entity_type);
    if (empty($data)) {
      $build['no_results'] = [
        '#type' => 'markup',
        '#markup' => '<p><strong>' . $this->t('No @type found.', ['@type' => $entity_type . 's']) . '</strong></p>',
      ];
      return $build;
    }

    $form_state->set($entity_type . '_data', $data);
    $labels = ['artist' => $this->t('Artist Name'), 'album' => $this->t('Album Name'), 'song' => $this->t('Song Name')];
    $button_texts = ['artist' => $this->t('Add Selected Artist'), 'album' => $this->t('Add Selected Album'), 'song' => $this->t('Add Selected Song')];
    
    $label = $labels[$entity_type] ?? $this->t('Name');
    $selected_key = 'selected_' . $entity_type;
    $submit_method = 'submitSelected' . ucfirst($entity_type);
    $button_text = $button_texts[$entity_type] ?? $this->t('Add Selected');

    $build['results_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Results (@count found)', ['@count' => count($data)]),
      '#collapsible' => FALSE,
    ];
    $build['results_fieldset']['results_table'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Select'), 'style' => 'width: 50px; text-align: center;'],
        ['data' => $label, 'style' => 'width: auto;'],
      ],
      '#empty' => $this->t('No results found.'),
    ];

    foreach ($data as $index => $item) {
      $build['results_fieldset']['results_table'][$index]['select'] = [
        '#type' => 'radio',
        '#return_value' => json_encode([
          'name' => $item['name'],
          'spotify_id' => $item['spotify_id'] ?? '',
          'discogs_id' => $item['discogs_id'] ?? '',
        ]),
        '#attributes' => ['title' => $this->t('Select @name', ['@name' => $item['name']])],
        '#parents' => [$selected_key],
      ];
      $build['results_fieldset']['results_table'][$index]['name'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('@name', ['@name' => $item['name']]) . '</strong>',
      ];
    }

    $build['results_fieldset']['add_selected'] = [
      '#type' => 'submit',
      '#value' => $button_text,
      '#name' => 'add_selected_' . $entity_type,
      '#submit' => ['::' . $submit_method],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity_type = $form_state->getValue('entity_type');
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#name']) && $triggering_element['#name'] === 'add_selected') {
      return;
    }
    if (isset($triggering_element['#name']) && strpos($triggering_element['#name'], 'add_selected_') === 0) {
      return;
    }
    if (in_array($entity_type, ['artist', 'album', 'song'])) {
      $form_state->setRebuild();
      return;
    }
  }

  /**
   * Submit handler for adding selected artist.
   */
  public function submitSelectedArtist(array &$form, FormStateInterface $form_state): void {
    $this->handleSelectedEntity('artist', 'music_db.data_select_artist', $form_state);
  }

  /**
   * Submit handler for adding selected album.
   */
  public function submitSelectedAlbum(array &$form, FormStateInterface $form_state): void {
    $this->handleSelectedEntity('album', 'music_db.data_select_album', $form_state);
  }

  /**
   * Submit handler for adding selected song.
   */
  public function submitSelectedSong(array &$form, FormStateInterface $form_state): void {
    $selected_value = $form_state->getValue('selected_song');
    if (empty($selected_value)) {
      $this->messenger()->addWarning($this->t('Please select a song.'));
      $form_state->setRebuild();
      return;
    }

    $selected = json_decode($selected_value, TRUE);
    if (!$selected || !isset($selected['name'])) {
      $this->messenger()->addError($this->t('Invalid song data.'));
      $form_state->setRebuild();
      return;
    }

    $tempstore = $this->tempStoreFactory->get('music_db');
    $tempstore->set('selected_song', [
      'name' => $selected['name'],
      'spotify_id' => $selected['spotify_id'] ?? '',
      'discogs_id' => $selected['discogs_id'] ?? '',
    ]);

    $this->messenger()->addMessage($this->t('Song selected: @name', ['@name' => $selected['name']]));
    $form_state->setRebuild();
  }

  /**
   * Handles selected entity submission.
   */
  protected function handleSelectedEntity(string $entity_type, string $route_name, FormStateInterface $form_state): void {
    $selected_value = $form_state->getValue('selected_' . $entity_type);
    if (empty($selected_value)) {
      $this->messenger()->addWarning($this->t('Please select a @type.', ['@type' => $entity_type]));
      $form_state->setRebuild();
      return;
    }

    $selected = json_decode($selected_value, TRUE);
    if (!$selected || !isset($selected['name'])) {
      $this->messenger()->addError($this->t('Invalid @type data.', ['@type' => $entity_type]));
      $form_state->setRebuild();
      return;
    }

    $tempstore = $this->tempStoreFactory->get('music_db');
    $tempstore->set('selected_' . $entity_type, [
      'name' => $selected['name'],
      'spotify_id' => $selected['spotify_id'] ?? '',
      'discogs_id' => $selected['discogs_id'] ?? '',
    ]);

    $spotify_id = !empty($selected['spotify_id']) ? $selected['spotify_id'] : 'none';
    $discogs_id = !empty($selected['discogs_id']) ? $selected['discogs_id'] : 'none';

    $form_state->setRedirect($route_name, [
      'spotify_id' => $spotify_id,
      'discogs_id' => $discogs_id,
    ]);
  }

}
