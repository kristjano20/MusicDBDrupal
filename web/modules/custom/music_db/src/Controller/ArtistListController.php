<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ArtistListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new ArtistListController.
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
   * Page callback: list artists.
   *
   * @return array
   *   A render array.
   */
  public function list() {
    $storage = $this->entityTypeManager->getStorage('artist');

    $query = $storage->getQuery()
      ->accessCheck(TRUE);

    $query->sort('name', 'ASC');

    $ids = $query->execute();
    $artists = $ids ? $storage->loadMultiple($ids) : [];

    $items = [];
    foreach ($artists as $artist) {
      $items[] = $artist->toLink()->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No artists found.'),
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('artist')->getListCacheTags(),
      ],
    ];
  }

}
