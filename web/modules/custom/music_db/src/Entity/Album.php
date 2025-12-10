<?php

namespace Drupal\music_db\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\music_db\AlbumAccessControlHandler;
use Drupal\music_db\AlbumListBuilder;
use Drupal\music_db\Form\AlbumDeleteForm;
use Drupal\music_db\Form\AlbumForm;

#[ContentEntityType(
  id: 'album',
  label: new TranslatableMarkup('Album'),

  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
    'revision' => 'revision_id',
  ],

  handlers: [
    'access' => AlbumAccessControlHandler::class,
    'list_builder' => AlbumListBuilder::class,
    'form' => [
      'add' => AlbumForm::class,
      'edit' => AlbumForm::class,
      'delete' => AlbumDeleteForm::class,
    ],
  ],
  links: [
    'canonical' => '/album/{album}',
    'add-form' => '/album/add',
    'edit-form' => '/album/{album}/edit',
    'delete-form' => '/album/{album}/delete',
    'collection' => '/admin/content/albums',
  ],
  admin_permission: 'administer music db',

  base_table: 'album',

  revision_table: 'album_revision',
)]
class Album extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Album title'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['artist'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Artist'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['released'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Released'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['record_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Record label'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['album_cover'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Album cover'))
      ->setDescription(t('Image selected from media library.'))
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          //Allow only image media
          'image' => 'image',
        ]
      ])
      -> setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 4,
        'settings' => [
          'media_types' => ['image'],
        ]
      ])
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 4,
        'settings' => [
          // Restrict the dialog to image media types.
          'media_types' => ['image'],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'weight' => 4,
        'settings' => [
          'view_mode' => 'default',
          'link' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['about'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('About'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['spotify_id'] = BaseFieldDefinition::create('string')
      ->setDisplayOptions('view', [
        'weight' => 6,
      ])
      ;
    $fields['discogs_id'] = BaseFieldDefinition::create('string')
      ->setDisplayOptions('view', [
        'weight' => 7,
      ])
    ;
    $fields['created'] = BaseFieldDefinition::create('created');
    $fields['changed'] = BaseFieldDefinition::create('changed');

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
