<?php

namespace Drupal\music_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ArtistListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['name'] = $this->t('Artist');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
