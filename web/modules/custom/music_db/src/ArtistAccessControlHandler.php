<?php

namespace Drupal\music_db;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class ArtistAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess($entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view artist');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit artist');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete artist');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add artist');
  }

}
