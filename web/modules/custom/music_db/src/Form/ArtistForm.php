<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class ArtistForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
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
