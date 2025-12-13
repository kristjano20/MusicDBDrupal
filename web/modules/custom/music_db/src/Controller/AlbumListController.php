<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a listing page for Album entities.
 */
final class AlbumListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new AlbumListController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Page callback: list albums.
   *
   * @return array
   *   A render array.
   */
  public function list() {
    $storage = $this->entityTypeManager->getStorage('album');

    $query = $storage->getQuery()
      ->accessCheck(TRUE);

    $query->sort('title', 'ASC');

    $ids = $query->execute();
    $albums = $ids ? $storage->loadMultiple($ids) : [];

    $items = [];
    foreach ($albums as $album) {
      $items[] = $album->toLink()->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No albums found.'),
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('album')->getListCacheTags(),
      ],
    ];
  }

}
