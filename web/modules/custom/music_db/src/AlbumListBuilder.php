<?php

namespace Drupal\music_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class AlbumListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['title'] = $this->t('Title');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['title'] = $entity->toLink();
    return $row + parent::buildRow($entity);
  }

}
