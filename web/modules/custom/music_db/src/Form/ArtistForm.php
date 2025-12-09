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
    $form['spotify_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SpotifyID'),
      '#disabled' => TRUE,
      '#required' => FALSE,
    ];
    $form['discogs_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DiscogsID'),
      '#disabled' => TRUE,
      '#required' => FALSE,
    ];

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
