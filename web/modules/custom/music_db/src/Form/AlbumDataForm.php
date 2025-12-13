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

    $form['name_section']['name_source'] = [
      '#type' => 'radios',
      '#options' => ['spotify' => '', 'discogs' => ''],
      '#default_value' => 'spotify',
      '#attributes' => ['class' => ['hidden']],
      '#theme_wrappers' => [],
    ];

    $form['name_section']['spotify_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'spotify',
        '#parents' => ['name_source'],
        '#attributes' => ['checked' => 'checked'],
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
        '#markup' => '<strong>Discogs:</strong> ' . ($discogs_data['title'] ?? $discogs_data['name'] ?? 'N/A'),
      ],
    ];

    // --------------------------------------------------------------------
    // SECTION: IMAGE
    // --------------------------------------------------------------------
    $form['image_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Album Cover'),
      '#attributes' => ['class' => ['select-section']],
      '#open' => FALSE,
    ];

    $form['image_section']['image_source'] = [
      '#type' => 'hidden',
      '#default_value' => 'none',
    ];

    $spotify_img = !empty($spotify_data['images'][0]['url'])
      ? '<img src="' . $spotify_data['images'][0]['url'] . '" width="300" />'
      : 'No image';

    $discogs_img = 'No image';
    if (!empty($discogs_data['images'][0])) {
      $discogs_image_url = $discogs_data['images'][0]['uri'] ?? $discogs_data['images'][0]['resource_url'] ?? NULL;
      if ($discogs_image_url) {
        $discogs_img = '<img src="' . $discogs_image_url . '" width="300" />';
      }
    }

    $form['#attached']['library'][] = 'core/drupal';
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
    // SECTION: RELEASE DATE
    // --------------------------------------------------------------------
    $form['release_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Release Date'),
      '#open' => FALSE,
    ];

    $form['release_section']['release_source'] = [
      '#type' => 'hidden',
      '#default_value' => 'none',
    ];

    $spotify_release = $spotify_data['release_date'] ?? '';
    $discogs_release = $discogs_data['year'] ?? $discogs_data['released'] ?? '';

    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => "
          (function () {
            document.addEventListener('DOMContentLoaded', function () {
              document.querySelectorAll('.release-option-radio').forEach(function(el) {
                el.addEventListener('change', function() {
                  var hidden = document.querySelector('[name=\"release_source\"]');
                  if (hidden) { hidden.value = this.value; }
                });
              });
            });
          })();
        ",
      ],
      'music_db_release_radio_sync',
    ];

    $form['release_section']['none_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['source-row']],
      'radio' => [
        '#type' => 'radio',
        '#return_value' => 'none',
        '#attributes' => ['class' => ['release-option-radio']],
      ],
      'label' => [
        '#markup' => '<strong>Don\'t save release date</strong>',
      ],
    ];

    if ($spotify_release) {
      $form['release_section']['spotify_row'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['source-row']],
        'radio' => [
          '#type' => 'radio',
          '#return_value' => 'spotify',
          '#attributes' => ['class' => ['release-option-radio']],
        ],
        'data' => [
          '#markup' => '<strong>Spotify:</strong> ' . $spotify_release,
        ],
      ];
    }

    if ($discogs_release) {
      $form['release_section']['discogs_row'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['source-row']],
        'radio' => [
          '#type' => 'radio',
          '#return_value' => 'discogs',
          '#attributes' => ['class' => ['release-option-radio']],
        ],
        'data' => [
          '#markup' => '<strong>Discogs:</strong> ' . $discogs_release,
        ],
      ];
    }

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
    $name_source = $form_state->getValue('name_source') ?? 'spotify';
    $image_source = $form_state->getValue('image_source') ?? 'none';
    $release_source = $form_state->getValue('release_source') ?? 'none';

    $spotify_id = $form_state->get('spotify_id');
    $discogs_id = $form_state->get('discogs_id');
    $spotify_data = $form_state->get('spotify_data') ?? [];
    $discogs_data = $form_state->get('discogs_data') ?? [];

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

    $selected_image_url = NULL;
    if ($image_source === 'spotify') {
      $selected_image_url = $spotify_data['images'][0]['url'] ?? NULL;
    } elseif ($image_source === 'discogs') {
      if (!empty($discogs_data['images'][0])) {
        $selected_image_url = $discogs_data['images'][0]['uri'] ?? $discogs_data['images'][0]['resource_url'] ?? NULL;
      }
    }

    $selected_release = NULL;
    if ($release_source === 'spotify') {
      $selected_release = $spotify_data['release_date'] ?? NULL;
    } elseif ($release_source === 'discogs') {
      $selected_release = $discogs_data['year'] ?? $discogs_data['released'] ?? NULL;
    }

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

    $storage = \Drupal::entityTypeManager()->getStorage('album');
    /** @var \Drupal\music_db\Entity\Album $entity */
    $entity = $storage->create($values);

    if (!empty($selected_image_url)) {
      $media_id = $this->downloadImageToMedia($selected_image_url, $selected_name);
      if ($media_id) {
        $entity->set('album_cover', ['target_id' => $media_id]);
      } else {
        \Drupal::logger('music_db')->warning('Image chosen but failed to create media from URL: @url', ['@url' => $selected_image_url]);
      }
    }

    $entity->save();

    $this->messenger()->addStatus($this->t('Album %name created.', ['%name' => $entity->label()]));
    \Drupal::logger('music_db')->notice('Album created: @id / @label', ['@id' => $entity->id(), '@label' => $entity->label()]);
  }

}

