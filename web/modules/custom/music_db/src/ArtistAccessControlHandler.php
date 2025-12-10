<?php

namespace Drupal\music_db;


use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class ArtistAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess($entity, $operation, AccountInterface $account) {
    if ($account->id() == 1) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        // One permission to rule them all.
        return AccessResult::allowedIfHasPermission($account, 'administer artist entities')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();

   /* switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view artist');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit artist');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete artist');
    }

    return AccessResult::neutral(); */
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->id() == 1) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer artist entities')
      ->cachePerPermissions();

   /* return AccessResult::allowedIfHasPermission($account, 'add artist'); */
  }

}
