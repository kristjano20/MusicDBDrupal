<?php

namespace Drupal\music_db\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Url;

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
   * @param string|null $letter
   *   Optional A-Z letter filter.
   *
   * @return array
   *   A render array.
   */
  public function list($letter = NULL) {
    $active_letter = $letter ? strtoupper($letter) : NULL;

    $links = [];
    $links['all'] = [
      'title' => $this->t('All'),
      'url' => Url::fromRoute('music_db.custom_artists'),
      'attributes' => [
        'class' => !$active_letter ? ['is-active'] : [],
      ],
    ];

    foreach (range('A', 'Z') as $char) {
      $links[$char] = [
        'title' => $char,
        'url' => Url::fromRoute('music_db.custom_artists_letter', ['letter' => $char]),
        'attributes' => [
          'class' => ($active_letter === $char) ? ['is-active'] : [],
        ],
      ];
    }

    $storage = $this->entityTypeManager->getStorage('artist');
    $query = $storage->getQuery()->accessCheck(TRUE);

    $label_field = 'name'; 

    if ($active_letter) {
      $query->condition($label_field, $active_letter, 'STARTS_WITH');
    }

    $query->sort($label_field, 'ASC');

    $ids = $query->execute();
    $artists = $ids ? $storage->loadMultiple($ids) : [];

    $items = [];
    foreach ($artists as $artist) {
      $items[] = $artist->toLink()->toRenderable();
    }

    return [
      'glossary' => [
        '#theme' => 'links',
        '#links' => $links,
        '#attributes' => ['class' => ['artist-glossary']],
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $items,
        '#empty' => $active_letter
          ? $this->t('No artists found starting with @letter.', ['@letter' => $active_letter])
          : $this->t('No artists found.'),
      ],
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('artist')->getListCacheTags(),
        'contexts' => ['url.path'],
      ],
    ];
  }


}
