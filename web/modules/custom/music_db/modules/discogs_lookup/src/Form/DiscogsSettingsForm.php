<?php
namespace Drupal\discogs_lookup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Discogs API Module Settings
 */

class DiscogsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'discogs_lookup_settings_form';
  }

  /**
   * {@inheritdoc}
   */

  protected function getEditableConfigNames() {
    return ['discogs_lookup.settings'];
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('discogs_lookup.settings');

    //Discogs API URI
    $form['api_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URI'),
      '#default_value' => $config->get('api_uri') ?: 'https://api.discogs.com/database/search',
      '#required' => FALSE,
      '#description' => $this->t('The base path to Discogs API.'),
    ];

    //Max hits
    $form['max_hits'] = [
      '#type' => 'number',
      '#title' => $this->t('Max hits'),
      '#default_value' => $config->get('max_hits') ?: 20,
      '#min' => 1,
      '#description' => $this->t('Maximum number of results returned from a search.'),
    ];

    //Collapsable group
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('Discogs API configuration'),
      '#open' => FALSE,
    ];

    $form['api']['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $config->get('token'),
      '#required' => FALSE,
      '#description' => $this->t('Discogs API token. Optional but recommended for higher rate limits.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('discogs_lookup.settings')
      ->set('api_uri', $form_state->getValue('api_uri'))
      ->set('max_hits', (int) $form_state->getValue('max_hits'))
      ->set('token', $form_state->getValue('token'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

