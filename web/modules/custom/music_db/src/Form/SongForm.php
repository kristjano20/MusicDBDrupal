<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class SongForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created new song %label.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Updated song %label.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.song.collection');
  }

}
