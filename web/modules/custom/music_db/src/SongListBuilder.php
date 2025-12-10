<?php

namespace Drupal\music_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class SongListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['title'] = $this->t('Song');
    $header['duration'] = $this->t('Duration');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['title'] = $entity->toLink();
    $row['duration'] = $entity->get('duration')->value;
    return $row + parent::buildRow($entity);
  }

}
