<?php
namespace Drupal\spotify_lookup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Spotify API Module Settings
 */

class SpotifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'spotify_lookup_settings_form';
  }

  /**
   * {@inheritdoc}
   */

  protected function getEditableConfigNames() {
    return ['spotify_lookup.settings'];
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('spotify_lookup.settings');


    //Spotify API URI
    $form['api_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URI'),
      '#default_value' => $config->get('api_uri') ?: 'https://api.spotify.com/v1',
      '#required' => TRUE,
      '#description' => $this->t('The base path to Spotify API.'),
    ];

    //Cache lifetime
    $form['cache_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache lifetime'),
      '#default_value' => $config->get('cache_lifetime') ?: 86400,
      '#min' => 0,
      '#description' => $this->t('Cache lifetime in seconds. (86400s is 24 hours'),
    ];

    //Max hits
    $form['max_hits'] = [
      '#type' => 'number',
      '#title' => $this->t('Max hits'),
      '#default_value' => $config->get('max_hits') ?: 20,
      '#min' => 1,
      '#description' => $this->t('Maximum number of results returned from a search.'),
    ];

    //Collapsable grousp
    $form['client'] = [
      '#type' => 'details',
      '#title' => $this->t('Spotify API Client configuration'),
      '#open' => FALSE,
    ];

    $form['client']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
      '#description' => $this->t('Spotify application Client ID.'),
    ];

    $form['client']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
      '#description' => $this->t('Spotify application Client Secret.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('spotify_lookup.settings')
      ->set('api_uri', $form_state->getValue('api_uri'))
      ->set('cache_lifetime', (int) $form_state->getValue('cache_lifetime'))
      ->set('max_hits', (int) $form_state->getValue('max_hits'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
