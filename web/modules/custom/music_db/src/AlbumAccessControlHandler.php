<?php

namespace Drupal\music_db;


use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class AlbumAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess($entity, $operation, AccountInterface $account) {
    if ($account->id() == 1) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        // One permission to rule them all.
        return AccessResult::allowedIfHasPermission($account, 'administer album entities')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();

    /* switch ($operation) {
       case 'view':
         return AccessResult::allowedIfHasPermission($account, 'view album');

       case 'edit':
         return AccessResult::allowedIfHasPermission($account, 'edit album');

       case 'delete':
         return AccessResult::allowedIfHasPermission($account, 'delete album');
     }

     return AccessResult::neutral(); */
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->id() == 1) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer album entities')
      ->cachePerPermissions();

    /* return AccessResult::allowedIfHasPermission($account, 'add artist'); */
  }

}
