<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\spotify_lookup\SpotifyLookupService;
use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Form for selecting which external album data to import (Spotify/Discogs).
 */
class AlbumDataForm extends FormBase {

  /** @var SpotifyLookupService  */
  protected $spotifyService;

  /** @var DiscogsLookupService  */
  protected $discogsService;

  /**
   * @inheritDoc
   */
  public function __construct(SpotifyLookupService $spotifyService, DiscogsLookupService $discogsService) {
    $this->spotifyService = $spotifyService;
    $this->discogsService = $discogsService;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spotify_lookup.search'),
      $container->get('discogs_lookup.search')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'music_db_album_data_form';
  }

  /**
   * Build the selection UI.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $spotify_id = NULL, $discogs_id = NULL) {

    // --------------------------------------------------------------------
    // Fetch external data
    // --------------------------------------------------------------------
    $spotify_data = [];
    if (!empty($spotify_id) && $spotify_id !== 'none' && $spotify_id !== '0') {
      try {
        $spotify_data = $this->spotifyService->getAlbum($spotify_id);
      }
      catch (\Throwable $e) {
        $spotify_data = [];
      }
    }

    $discogs_data = [];
    if (!empty($discogs_id) && $discogs_id !== 'none' && $discogs_id !== '0') {
      try {
        $discogs_data = $this->discogsService->getAlbum($discogs_id);
      }
      catch (\Throwable $e) {
        $discogs_data = [];
      }
    }

    // Persist the fetched API data so submitForm() can access it.
    $form_state->set('spotify_data', $spotify_data);
    $form_state->set('discogs_data', $discogs_data);

    // Also persist the incoming ids.
    $form_state->set('spotify_id', $spotify_id);
    $form_state->set('discogs_id', $discogs_id);

    // --------------------------------------------------------------------
    // SECTION: NAME
    // --------------------------------------------------------------------
    $form['name_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Title'),
      '#open' => TRUE,
    ];

    $form['name_section']['name_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose name source'),
      '#options' => [
        'spotify' => $this->t('<strong>Spotify: @name</strong>', [
          '@name' => $spotify_data['name'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['title'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'spotify',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: IMAGE
    // --------------------------------------------------------------------
    $form['image_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Image'),
      '#open' => FALSE,
    ];

    $spotify_img_markup = !empty($spotify_data['images'][0]['url'])
      ? '<img src="' . $spotify_data['images'][0]['url'] . '" width="300" />'
      : '<em>No image available</em>';

    $discogs_img_markup = !empty($discogs_data['images'][0]['uri'])
      ? '<img src="' . $discogs_data['images'][0]['uri'] . '" width="300" />'
      : '<em>No image available</em>';

    $form['image_section']['image_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose image source'),
      '#options' => [
        'none' => '<strong>Don’t save an image</strong>',
        'spotify' => '<strong>Spotify</strong><br>' . $spotify_img_markup,
        'discogs' => '<strong>Discogs</strong><br>' . $discogs_img_markup,
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SECTION: RELEASE DATE
    // --------------------------------------------------------------------
    $form['release_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Release Date'),
      '#open' => FALSE,
    ];

    $form['release_section']['release_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose name source'),
      '#options' => [
        'none' => '<strong>Don\'t save release date</strong>',
        'spotify' => $this->t('<strong>Spotify: @name</strong>', [
          '@name' => $spotify_data['release_date'] ?? 'N/A',
        ]),
        'discogs' => $this->t('<strong>Discogs: @name</strong>', [
          '@name' => $discogs_data['year'] ?? $discogs_data['realeased'] ?? 'N/A',
        ]),
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SUBMIT
    // --------------------------------------------------------------------
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Selected Data'),
    ];

    return $form;
  }

  /**
   * Download a remote image, save it to public:// and create a Media image entity.
   */
  protected function downloadImageToMedia(string $url, string $name): ?int {
    if (empty($url)) {
      return NULL;
    }

    $data = @file_get_contents($url);
    if ($data === FALSE || $data === NULL) {
      \Drupal::logger('music_db')->warning('Failed to fetch image from URL: @url', ['@url' => $url]);
      return NULL;
    }

    $directory = 'public://album_covers';
    \Drupal::service('file_system')->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    $ext = 'jpg';
    $u = @parse_url($url);
    if (!empty($u['path'])) {
      $maybe_ext = pathinfo($u['path'], PATHINFO_EXTENSION);
      if (!empty($maybe_ext)) {
        $maybe_ext = strtolower(preg_replace('/[^a-z0-9]+/', '', $maybe_ext));
        if (in_array($maybe_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
          $ext = $maybe_ext;
        }
      }
    }

    $safe_base = preg_replace('/[^a-z0-9_]+/i', '_', $name ?: 'album_image');
    $filename = $safe_base . '.' . $ext;
    $destination_uri = $directory . '/' . $filename;

    $file_repository = \Drupal::service('file.repository');
    try {
      $file = $file_repository->writeData($data, $destination_uri, FileSystemInterface::EXISTS_RENAME);
    }
    catch (\Exception $e) {
      \Drupal::logger('music_db')->error('writeData failed for @uri: @message', ['@uri' => $destination_uri, '@message' => $e->getMessage()]);
      return NULL;
    }

    if (!$file instanceof File) {
      \Drupal::logger('music_db')->error('Failed to create file entity for @uri', ['@uri' => $destination_uri]);
      return NULL;
    }

    $file->setPermanent();
    $file->save();

    try {
      $media = Media::create([
        'bundle' => 'image',
        'name' => $name ?: $filename,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $name ?: '',
        ],
        'status' => 1,
      ]);
      $media->save();
    }
    catch (\Exception $e) {
      try {
        $file->delete();
      } catch (\Exception $_) {}
      \Drupal::logger('music_db')->error('Failed to create media for file @fid: @message', ['@fid' => $file->id(), '@message' => $e->getMessage()]);
      return NULL;
    }

    return $media->id();
  }

  /**
   * Formats release date to YYYY-MM-DD format.
   */
  protected function formatReleaseDate(string $release): ?string {
    // Spotify: "2023-01-15" or "2023-01" or "2023"
    // Discogs: "2023" or "2023-01-15"
    if (preg_match('/^(\d{4})(?:-(\d{2}))?(?:-(\d{2}))?$/', $release, $matches)) {
      $year = $matches[1];
      $month = $matches[2] ?? '01';
      $day = $matches[3] ?? '01';
      return $year . '-' . $month . '-' . $day;
    }
    // If just a year
    if (preg_match('/^\d{4}$/', $release)) {
      return $release . '-01-01';
    }
    return NULL;
  }

  /**
   * Store selected values.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the choices
    $name_source = $form_state->getValue('name_source') ?? 'spotify';
    $image_source = $form_state->getValue('image_source') ?? 'none';
    $release_source = $form_state->getValue('release_source') ?? 'none';

    // Get persisted API data and IDs that were stored in buildForm()
    $spotify_id = $form_state->get('spotify_id');
    $discogs_id = $form_state->get('discogs_id');
    $spotify_data = $form_state->get('spotify_data') ?? [];
    $discogs_data = $form_state->get('discogs_data') ?? [];

    // Selected title
    $selected_name = NULL;
    if ($name_source === 'spotify') {
      $selected_name = $spotify_data['name'] ?? NULL;
    } elseif ($name_source === 'discogs') {
      $selected_name = $discogs_data['title'] ?? $discogs_data['name'] ?? NULL;
    }

    if (empty($selected_name)) {
      $this->messenger()->addError($this->t('Please select a name before saving.'));
      return;
    }

    // Selected image URL (if any)
    $selected_image_url = NULL;
    if ($image_source === 'spotify') {
      $selected_image_url = $spotify_data['images'][0]['url'] ?? NULL;
    } elseif ($image_source === 'discogs') {
      if (!empty($discogs_data['images'][0])) {
        $selected_image_url = $discogs_data['images'][0]['uri'] ?? $discogs_data['images'][0]['resource_url'] ?? NULL;
      }
    }

    // Selected release date (if any)
    $selected_release = NULL;
    if ($release_source === 'spotify') {
      $selected_release = $spotify_data['release_date'] ?? NULL;
    } elseif ($release_source === 'discogs') {
      $selected_release = $discogs_data['year'] ?? $discogs_data['released'] ?? NULL;
    }

    // Build the values for the entity.
    $values = [
      'title' => $selected_name,
      'spotify_id' => $spotify_id ?: '',
      'discogs_id' => $discogs_id ?: '',
    ];

    if ($selected_release) {
      // Format release date for date field (YYYY-MM-DD format)
      $release_date = $this->formatReleaseDate($selected_release);
      if ($release_date) {
        $values['released'] = $release_date;
      }
    }

    // Create entity storage and entity.
    $storage = \Drupal::entityTypeManager()->getStorage('album');
    /** @var \Drupal\music_db\Entity\Album $entity */
    $entity = $storage->create($values);

    // If an image was chosen, download and create a Media entity and set photo.
    if (!empty($selected_image_url)) {
      $media_id = $this->downloadImageToMedia($selected_image_url, $selected_name);
      if ($media_id) {
        // entity reference field expects array with target_id for base fields.
        $entity->set('album_cover', ['target_id' => $media_id]);
      } else {
        $this->messenger()->addError($this->t('Image download failed, no image saved.'));
      }
    }

    $entity->save();

    $this->messenger()->addStatus($this->t('Album %title created.', [
      '%title' => $entity->label(),
    ]));

    // Redirect to the newly created entity.
    $form_state->setRedirectUrl($entity->toUrl());
  }

}

