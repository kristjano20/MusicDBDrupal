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
 * Form for selecting which external data to import (Spotify/Discogs).
 */
class ArtistDataForm extends FormBase {

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
    return 'music_db_artist_data_form';
  }

  /**
   * Build the selection UI.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $spotify_id = NULL, $discogs_id = NULL) {

    // --------------------------------------------------------------------
    // Fetch external data
    // --------------------------------------------------------------------
    // Only fetch data if IDs are provided and not empty/invalid.
    $spotify_data = [];
    if (!empty($spotify_id) && $spotify_id !== 'none' && $spotify_id !== '0') {
      try {
        $spotify_data = $this->spotifyService->getArtist($spotify_id);
      }
      catch (\Throwable $e) {
        $spotify_data = [];
      }
    }

    $discogs_data = [];
    if (!empty($discogs_id) && $discogs_id !== 'none' && $discogs_id !== '0') {
      try {
        $discogs_data = $this->discogsService->getArtist($discogs_id);
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
      '#title' => $this->t('Name'),
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
          '@name' => $discogs_data['name'] ?? 'N/A',
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
    // SECTION: ABOUT / BIO
    // --------------------------------------------------------------------
    $form['bio_section'] = [
      '#type' => 'details',
      '#title' => $this->t('About'),
      '#open' => FALSE,
    ];

    $discogs_bio_markup = !empty($discogs_data['profile'])
      ? nl2br(htmlspecialchars($discogs_data['profile']))
      : '<em>No bio available from Discogs.</em>';

    $form['bio_section']['bio_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose bio source'),
      '#options' => [
        'none' => '<strong>Don’t save a bio</strong>',
        'discogs' => '<strong>Discogs bio</strong><br>' . $discogs_bio_markup,
      ],
      '#default_value' => 'none',
      '#escape' => FALSE,
    ];

    // --------------------------------------------------------------------
    // SUBMIT
    // --------------------------------------------------------------------
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Create Artist'),
    ];

    return $form;
  }

  /**
   * Download a remote image, save it to public:// and create a Media image entity.
   *
   * @param string $url
   *   Remote image URL.
   * @param string $name
   *   Name/label to use for the Media (used for filename and alt).
   *
   * @return int|null
   *   Media entity ID on success, or NULL on failure.
   */
  protected function downloadImageToMedia(string $url, string $name): ?int {
    if (empty($url)) {
      return NULL;
    }

    // Try to fetch binary data (suppress warnings).
    $data = @file_get_contents($url);
    if ($data === FALSE || $data === NULL) {
      \Drupal::logger('music_db')->warning('Failed to fetch image from URL: @url', ['@url' => $url]);
      return NULL;
    }

    // Ensure directory exists.
    $directory = 'public://artist_photos';
    \Drupal::service('file_system')->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    // Determine extension from URL if possible.
    $ext = 'jpg';
    $u = @parse_url($url);
    if (!empty($u['path'])) {
      $maybe_ext = pathinfo($u['path'], PATHINFO_EXTENSION);
      if (!empty($maybe_ext)) {
        // sanitize extension and limit to common types
        $maybe_ext = strtolower(preg_replace('/[^a-z0-9]+/', '', $maybe_ext));
        if (in_array($maybe_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
          $ext = $maybe_ext;
        }
      }
    }

    // Safe filename
    $safe_base = preg_replace('/[^a-z0-9_]+/i', '_', $name ?: 'artist_image');
    $filename = $safe_base . '.' . $ext;
    $destination_uri = $directory . '/' . $filename;

    // Save file using file.repository->writeData() to get a File entity.
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

    // Mark permanent & save.
    $file->setPermanent();
    $file->save();

    // Create media entity (image bundle). Adjust field name if your site differs.
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
      // Clean up file on failure.
      try {
        $file->delete();
      } catch (\Exception $_) {}
      \Drupal::logger('music_db')->error('Failed to create media for file @fid: @message', ['@fid' => $file->id(), '@message' => $e->getMessage()]);
      return NULL;
    }

    return $media->id();
  }

  /**
   * Create a new artist with the selected data and the id's from spotify and discogs
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the choices
    $name_source  = $form_state->getValue('name_source') ?? 'spotify';
    $image_source = $form_state->getValue('image_source') ?? 'none';
    $bio_source   = $form_state->getValue('bio_source') ?? 'none';

    // Get persisted API data and IDs that were stored in buildForm().
    $spotify_id   = $form_state->get('spotify_id');
    $discogs_id   = $form_state->get('discogs_id');
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
      $this->messenger()->addError($this->t('Please select a name before saving.'));
      return;
    }

    // Selected image URL (if any)
    $selected_image_url = NULL;
    if ($image_source === 'spotify') {
      $selected_image_url = $spotify_data['images'][0]['url'] ?? NULL;
    } elseif ($image_source === 'discogs') {
      $selected_image_url = $discogs_data['images'][0]['uri'] ?? NULL;
    }

    // Selected bio
    $selected_bio = NULL;
    if ($bio_source === 'discogs') {
      $selected_bio = $discogs_data['profile'] ?? NULL;
    }

    // Build the values for the entity.
    $values = [
      'name' => $selected_name,
      'spotify_id' => $spotify_id ?: '',
      'discogs_id' => $discogs_id ?: '',
      'about' => $selected_bio ?: '',
    ];

    // Create entity storage and entity.
    $storage = \Drupal::entityTypeManager()->getStorage('artist');
    /** @var \Drupal\music_db\Entity\Artist $entity */
    $entity = $storage->create($values);

    // If an image was chosen, download and create a Media entity and set photo.
    if (!empty($selected_image_url)) {
      $media_id = $this->downloadImageToMedia($selected_image_url, $selected_name);
      if ($media_id) {
        // entity reference field expects array with target_id for base fields.
        $entity->set('photo', ['target_id' => $media_id]);
      } else {
        $this->messenger()->addError($this->t('Image download failed, no image saved.'));
      }
    }

    $entity->save();

    $this->messenger()->addStatus($this->t('Artist %name created.', [
      '%name' => $entity->label(),
    ]));

    // Redirect to the newly created entity.
    $form_state->setRedirectUrl($entity->toUrl());
  }
}
