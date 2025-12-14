<?php

namespace Drupal\music_db\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SongDataForm extends FormBase
{

  /** @var SpotifyLookupService */
  protected $spotifyService;

  /** @var DiscogsLookupService */
  protected $discogsService;

  /**
   * @inheritDoc
   */
  public function __construct(SpotifyLookupService $spotifyService, DiscogsLookupService $discogsService)
  {
    $this->spotifyService = $spotifyService;
    $this->discogsService = $discogsService;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('spotify_lookup.search'),
      $container->get('discogs_lookup.search')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'music_db_song_data_form';
  }

  /**
   * Build the selection UI.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $spotify_id = NULL, $discogs_id = NULL)
  {

    // --------------------------------------------------------------------
    // Fetch external data
    // --------------------------------------------------------------------
    // Only fetch data if IDs are provided and not empty/invalid.
    $spotify_data = [];
    if (!empty($spotify_id) && $spotify_id !== 'none' && $spotify_id !== '0') {
      try {
        $spotify_data = $this->spotifyService->getSong($spotify_id);
      } catch (\Throwable $e) {
        $spotify_data = [];
      }
    }

    $discogs_data = [];
    if (!empty($discogs_id) && $discogs_id !== 'none' && $discogs_id !== '0') {
      try {
        $discogs_data = $this->discogsService->getArtist($discogs_id);
      } catch (\Throwable $e) {
        $discogs_data = [];
      }
    }

    // Persist the fetched API data so submitForm() can access it.
    $form_state->set('spotify_data', $spotify_data);
    #$form_state->set('discogs_data', $discogs_data);

    // Also persist the incoming ids.
    $form_state->set('spotify_id', $spotify_id);
    #$form_state->set('discogs_id', $discogs_id);

    // --------------------------------------------------------------------
    // SECTION: SONG TITLE
    // --------------------------------------------------------------------
    $form['title_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Song title'),
      '#open' => TRUE,
    ];

    $form['title_section']['title_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose title source'),
      '#options' => [
        'spotify' => $this->t('<strong>Spotify: @title</strong>', [
          '@title' => $spotify_data['name'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['name'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'spotify',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: DURATION
    // --------------------------------------------------------------------
    $form['duration_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Song duration'),
      '#open' => FALSE,
    ];
    // Changing from miliseconds to minutes:seconds
    $minutes = floor($spotify_data['duration_ms'] / 60000);
    $seconds = floor(($spotify_data['duration_ms'] % 60000) / 1000);

    $spotify_duration = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

    $form['duration_section']['duration_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose duration source'),
      '#options' => [
        'none' => $this->t('<strong>None</strong>'),
        'spotify' => $this->t('<strong>Spotify: @duration</strong>', [
          '@duration' => $spotify_duration ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['name'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: ARTIST
    // --------------------------------------------------------------------
    $form['artist_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Artist'),
      '#open' => FALSE,
    ];

    $form['artist_section']['artist_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Artist source'),
      '#options' => [
        'none' => $this->t('<strong>None</strong>'),
        'spotify' => $this->t('<strong>Spotify: @artist</strong>', [
          '@artist' => $spotify_data['artists']['0']['name'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['name'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: ALBUM
    // --------------------------------------------------------------------
    $form['album_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Album'),
      '#open' => FALSE,
    ];

    $form['album_section']['album_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Album source'),
      '#options' => [
        'none' => $this->t('<strong>None</strong>'),
        'spotify' => $this->t('<strong>Spotify: @album</strong>', [
          '@album' => $spotify_data['album']['name'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['name'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: TRACK NUMBER
    // --------------------------------------------------------------------
    $form['track_no_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Track number'),
      '#open' => FALSE,
    ];

    $form['track_no_section']['track_no_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Track number source'),
      '#options' => [
        'none' => $this->t('<strong>None</strong>'),
        'spotify' => $this->t('<strong>Spotify: @track_no</strong>', [
          '@track_no' => $spotify_data['track_number'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['name'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // DEBUG
    // --------------------------------------------------------------------

    /*
    $form['debug_spotify'] = [
      '#type' => 'details',
      '#title' => $this->t('Spotify data (debug)'),
      '#open' => FALSE,
      '#markup' => '<pre>' . Html::escape(print_r($spotify_data, TRUE)) . '</pre>',
    ];

    $form['debug_discogs'] = [
      '#type' => 'details',
      '#title' => $this->t('Discogs data (debug)'),
      '#open' => FALSE,
      '#markup' => '<pre>' . Html::escape(print_r($discogs_data, TRUE)) . '</pre>',
    ];
    */
    // --------------------------------------------------------------------
    // SUBMIT
    // --------------------------------------------------------------------
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Create Song'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Get the choices
    $name_source = $form_state->getValue('name_source') ?? 'spotify';
    $duration_source = $form_state->getValue('duration_source') ?? '';
    $artist_source  = $form_state->getValue('artist_source') ?? '';
    $album_source = $form_state->getValue('album_source') ?? '';
    $track_no_source = $form_state->getValue('track_no_source') ?? '';

    // Get persisted API data and IDs that were stored in buildForm().
    $spotify_id = $form_state->get('spotify_id');
    $discogs_id = $form_state->get('discogs_id');
    $spotify_data = $form_state->get('spotify_data') ?? [];
    $discogs_data = $form_state->get('discogs_data') ?? [];

    // Selected name
    $selected_name = NULL;
    if ($name_source === 'spotify') {
      $selected_name = $spotify_data['name'] ?? NULL;
    } elseif ($name_source === 'discogs') {
      $selected_name = $discogs_data['name'] ?? NULL;
    }

    if (empty($selected_name)) {
      $this->messenger()->addError($this->t('Please select a title before saving.'));
      return;
    }

    // Selected duration (if any)
    $selected_duration = NULL;
    if ($duration_source === 'spotify') {

      // Switching from miliseconds to minutes:seconds
      $minutes = floor($spotify_data['duration_ms'] / 60000);
      $seconds = floor(($spotify_data['duration_ms'] % 60000) / 1000);

      $selected_duration = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
    elseif ($duration_source === 'discogs') {
      $selected_duration = NULL;
    }

    // Selected Artist (if any)
    $selected_artist = NULL;
    if ($artist_source === 'spotify') {
      $selected_artist = $spotify_data['artists']['0']['name'] ?? NULL;
    }
    elseif ($artist_source === 'discogs') {
      $selected_artist = NULL;
    }

    // Selected Album (if any)
    $selected_album = NULL;
    if ($album_source === 'spotify') {
      $selected_album = $spotify_data['album']['name'] ?? NULL;
    }
    elseif ($album_source === 'discogs') {
      $selected_album = NULL;
    }

    // Selected Track Number (if any)
    $selected_track_no = NULL;
    if ($track_no_source === 'spotify') {
      $selected_track_no = $spotify_data['track_number'] ?? NULL;
    }
    elseif ($track_no_source === 'discogs') {
      $selected_track_no = NULL;
    }

    // Build the values for the entity.
    $values = [
      'title' => $selected_name,
      'duration' => $selected_duration,
      'artist' => $selected_artist,
      'album' => $selected_album,
      'track_no' => $selected_track_no,
      'spotify_id' => $spotify_id ?: '',
      'discogs_id' => $discogs_id ?: '',
    ];

    // Create entity storage and entity.
    $storage = \Drupal::entityTypeManager()->getStorage('song');
    /** @var \Drupal\music_db\Entity\Song $entity */
    $entity = $storage->create($values);

    $entity->save();

    $this->messenger()->addStatus($this->t('Song %title created.', [
      '%title' => $entity->label(),
    ]));

    // Redirect to the newly created entity.
    $form_state->setRedirectUrl($entity->toUrl());

  }
}
