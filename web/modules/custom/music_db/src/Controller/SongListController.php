<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a listing page for Song entities.
 */
final class SongListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new SongListController.
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
   * Page callback: list songs.
   *
   * @return array
   *   A render array.
   */
  public function list() {
    $storage = $this->entityTypeManager->getStorage('song');

    $query = $storage->getQuery()
      ->accessCheck(TRUE);

    // Change 'title' if your song label field is different.
    $query->sort('title', 'ASC');

    $ids = $query->execute();
    $songs = $ids ? $storage->loadMultiple($ids) : [];

    $items = [];
    foreach ($songs as $song) {
      $items[] = $song->toLink()->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No songs found.'),
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('song')->getListCacheTags(),
      ],
    ];
  }

}
