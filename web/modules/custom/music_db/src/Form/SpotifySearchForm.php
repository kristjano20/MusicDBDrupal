<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_lookup\SpotifyLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SpotifySearchForm extends FormBase {

  /**
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected SpotifyLookupService $spotifyLookup;

  public function __construct(SpotifyLookupService $spotify_lookup) {
    $this->spotifyLookup = $spotify_lookup;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('spotify_lookup.search')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'music_db_spotify_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search query'),
      '#description' => $this->t('Example: @example', ['@example' => 'album:mayhem artist:lady gaga']),
      '#required' => TRUE,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Search type'),
      '#options' => [
        'track' => $this->t('Track'),
        'album' => $this->t('Album'),
        'artist' => $this->t('Artist'),
      ],
      '#default_value' => 'album',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search Spotify'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $query = $form_state->getValue('query');
    $type = $form_state->getValue('type');

    try {
      $results = $this->spotifyLookup->search($query, $type, 5);
        if (!empty($results[$type . 's']['items'])) {
        $count = count($results[$type . 's']['items']);
        $this->messenger()->addStatus($this->t('Found @count @type result(s) on Spotify.', [
          '@count' => $count,
          '@type' => $type,
        ]));
      }
      else {
        $this->messenger()->addWarning($this->t('No results found.'));
      }
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Error calling Spotify: @msg', [
        '@msg' => $e->getMessage(),
      ]));
    }
  }

}

