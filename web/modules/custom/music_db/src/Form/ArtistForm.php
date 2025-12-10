<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ArtistForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'artist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'music_db.spotify_artist_autocomplete',
      '#autocomplete_route_parameters' => [],
      '#description' => $this->t('Start typing to search Spotify and pick the correct artist.'),
    ];
    $form['date_of_birth'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Birth/Founded'),
      '#required' => FALSE,
    ];
    $form['date_of_death'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Death/Disbanded'),
      '#required' => FALSE,
    ];
    $form['artist_photo'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Select a Photo'),
      '#allowed_bundles' => ['image'],   // Only media type: image
      '#cardinality' => 1,               // Single image
      '#description' => $this->t('Choose an existing image or upload a new one.'),
      '#required' => TRUE,
    ];
    $form['about'] = [
      '#type' => 'textarea',
      '#title' => $this->t('About'),
      '#required' => FALSE,
    ];
    $id_fields = ['spotify_id' => 'SpotifyID', 'discogs_id' => 'DiscogsID'];
    foreach ($id_fields as $field_name => $field_title) {
      $form[$field_name] = [
        '#type' => 'textfield',
        '#title' => $this->t($field_title),
        '#required' => FALSE,
        '#default_value' => $this->entity->get($field_name)->value ?? '',
        '#attributes' => ['readonly' => 'readonly'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('New artist added.'));
    }
    else {
      $this->messenger()->addMessage($this->t('Artist updated.'));
    }

    $form_state->setRedirect('entity.artist.collection');
  }

}
