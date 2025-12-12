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

  protected $spotifyService;
  protected $discogsService;

  public function __construct(SpotifyLookupService $spotifyService, DiscogsLookupService $discogsService) {
    $this->spotifyService = $spotifyService;
    $this->discogsService = $discogsService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spotify_lookup.search'),
      $container->get('discogs_lookup.search')
    );
  }

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
    // SECTION: NAME (details element)
    // --------------------------------------------------------------------
    $form['name_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Name'),
      '#open' => TRUE,
    ];

    // Hidden radios element to store final value (kept for storage).
    $form['name_section']['name_source'] = [
      '#type' => 'radios',
      '#options' => ['spotify' => '', 'discogs' => ''],
      '#default_value' => 'spotify',
      '#attributes' => ['class' => ['hidden']],
      '#theme_wrappers' => [], // optional: prevent default wrapper if you hide it with CSS.
    ];

    $form['name_section']['spotify_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'spotify',
        '#parents' => ['name_source'],
        '#attributes' => ['checked' => 'checked'], // visual default
      ],
      'data' => [
        '#markup' => '<strong>Spotify:</strong> ' . ($spotify_data['name'] ?? 'N/A'),
      ],
    ];

    $form['name_section']['discogs_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'discogs',
        '#parents' => ['name_source'],
      ],
      'data' => [
        '#markup' => '<strong>Discogs:</strong> ' . ($discogs_data['name'] ?? 'N/A'),
      ],
    ];

    // --------------------------------------------------------------------
    // SECTION: IMAGE
    // --------------------------------------------------------------------
    $form['image_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Image'),
      '#attributes' => ['class' => ['select-section']],
      '#open' => FALSE,
    ];

    // Hidden field to store selection (instead of radios).
    $form['image_section']['image_source'] = [
      '#type' => 'hidden',
      '#default_value' => 'none',
    ];

    $spotify_img = !empty($spotify_data['images'][0]['url'])
      ? '<img src="' . $spotify_data['images'][0]['url'] . '" width="300" />'
      : 'No image';

    $discogs_img = !empty($discogs_data['images'][0]['uri'])
      ? '<img src="' . $discogs_data['images'][0]['uri'] . '" width="300" />'
      : 'No image';

    // Add a small JS that will keep the hidden field in sync with your manual radios.
    $form['#attached']['library'][] = 'core/drupal'; // ensure core drupal behaviors present
    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => "
          (function () {
            document.addEventListener('DOMContentLoaded', function () {
              document.querySelectorAll('.image-option-radio').forEach(function(el) {
                el.addEventListener('change', function() {
                  var hidden = document.querySelector('[name=\"image_source\"]');
                  if (hidden) {
                    hidden.value = this.value;
                  }
                });
              });
            });
          })();
        ",
      ],
      'music_db_image_radio_sync',
    ];

    // None row
    $form['image_section']['none_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'none',
        '#attributes' => ['class' => ['image-option-radio']],
      ],
      'label' => [
        '#markup' => '<em>Don\'t save image</em>',
      ],
    ];

    // Spotify row
    $form['image_section']['spotify_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'spotify',
        '#attributes' => ['class' => ['image-option-radio']],
      ],
      'data' => [
        '#markup' => '<strong>Spotify</strong> — ' . $spotify_img,
      ],
    ];

    // Discogs row
    $form['image_section']['discogs_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'discogs',
        '#attributes' => ['class' => ['image-option-radio']],
      ],
      'data' => [
        '#markup' => '<strong>Discogs</strong><br>' . $discogs_img,
      ],
    ];

    // --------------------------------------------------------------------
    // SECTION: ABOUT / BIO
    // --------------------------------------------------------------------
    $form['bio_section'] = [
      '#type' => 'details',
      '#title' => $this->t('About'),
      '#open' => FALSE,
    ];

    // Hidden field to store bio selection.
    $form['bio_section']['bio_source'] = [
      '#type' => 'hidden',
      '#default_value' => 'none',
    ];

    $discogs_bio_raw = $discogs_data['profile'] ?? '';
    // Strip discogs markup to make it readable in preview:
    $discogs_bio_clean = preg_replace('/\[(?:[a-z]=?)?[^\]]+\]/i', '', $discogs_bio_raw);
    $discogs_bio_clean = trim($discogs_bio_clean);
    $discogs_bio_rendered = $discogs_bio_clean ? nl2br(htmlspecialchars($discogs_bio_clean)) : '<em>No bio available from Discogs.</em>';

    // Add small JS for bio radios to update the hidden field.
    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => "
          (function () {
            document.addEventListener('DOMContentLoaded', function () {
              document.querySelectorAll('.bio-option-radio').forEach(function(el) {
                el.addEventListener('change', function() {
                  var hidden = document.querySelector('[name=\"bio_source\"]');
                  if (hidden) { hidden.value = this.value; }
                });
              });
            });
          })();
        ",
      ],
      'music_db_bio_radio_sync',
    ];

    $form['bio_section']['none_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'none',
        '#attributes' => ['class' => ['bio-option-radio']],
      ],
      'label' => [
        '#markup' => '<strong>Don\'t save bio</strong>',
      ],
    ];

    $form['bio_section']['discogs_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'discogs',
        '#attributes' => ['class' => ['bio-option-radio']],
      ],
      'label' => [
        '#markup' => '<strong>Discogs bio</strong>',
      ],
      'data' => [
        '#markup' => '<div class=\"bio-preview\">' . $discogs_bio_rendered . '</div>',
      ],
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
   * Store selected values.
   */
public function submitForm(array &$form, FormStateInterface $form_state) {
  // Gather selections (fall back to defaults).
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
    if ($selected_bio) {
      // Optionally strip internal Discogs tags:
      $selected_bio = trim(preg_replace('/\[(?:[a-z]=?)?[^\]]+\]/i', '', $selected_bio));
    }
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
      \Drupal::logger('music_db')->warning('Image chosen but failed to create media from URL: @url', ['@url' => $selected_image_url]);
      // Not fatal — continue saving without photo.
    }
  }

  // Save entity.
  $entity->save();

  $this->messenger()->addStatus($this->t('Artist %name created.', ['%name' => $entity->label()]));
  \Drupal::logger('music_db')->notice('Artist created: @id / @label', ['@id' => $entity->id(), '@label' => $entity->label()]);
}


}
