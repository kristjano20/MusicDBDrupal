<?php

namespace Drupal\music_db\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class SongForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    
    $id_fields = ['spotify_id', 'discogs_id'];
    foreach ($id_fields as $field_name) {
      if (isset($form[$field_name]['widget'][0]['value'])) {
        $form[$field_name]['widget'][0]['value']['#disabled'] = TRUE;
      }
      elseif (isset($form[$field_name])) {
        $form[$field_name]['#disabled'] = TRUE;
      }
    }
    
    return $form;
  }

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
