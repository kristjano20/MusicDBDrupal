<?php

namespace Drupal\music_db\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\music_db\ArtistAccessControlHandler;
use Drupal\music_db\ArtistListBuilder;
use Drupal\music_db\Form\ArtistDeleteForm;
use Drupal\music_db\Form\ArtistForm;

#[ContentEntityType(
  id: 'artist',
  label: new TranslatableMarkup('Artist'),

  handlers: [
    'access' => ArtistAccessControlHandler::class,
    'list_builder' => ArtistListBuilder::class,
    'form' => [
      'add' => ArtistForm::class,
      'edit' => ArtistForm::class,
      'delete' => ArtistDeleteForm::class,
    ],
  ],

  base_table: 'artist',
  revision_table: 'artist_revision',
  admin_permission: 'administer music db',

  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'name',
    'revision' => 'revision_id',
  ],

  links: [
    'canonical' => '/artist/{artist}',
    'add-form' => '/artist/add',
    'edit-form' => '/artist/{artist}/edit',
    'delete-form' => '/artist/{artist}/delete',
    'collection' => '/admin/content/artists',
  ]
)]
class Artist extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Artist Name'))
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

    $fields['date_of_birth'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of Birth/Formation'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['date_of_death'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of Death/Disband'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
    ;

    $fields['photo'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Photo of Artist'))
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
        'weight' => 20,
        'settings' => [
          'media_types' => ['image'],
        ]
      ])
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 20,
        'settings' => [
          // Restrict the dialog to image media types.
          'media_types' => ['image'],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'weight' => 20,
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
        'weight' => 30,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;

    $fields['autocomplete_provider'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Autocomplete Provider'))
      ->setDescription(t('Select which service to use for artist autocomplete search.'))
      ->setSettings([
        'allowed_values' => [
          'none' => t('None'),
          'spotify' => t('Spotify'),
          'discogs' => t('Discogs'),
        ],
      ])
      ->setDefaultValue('none')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['spotify_id'] = BaseFieldDefinition::create('string');
    $fields['discogs_id'] = BaseFieldDefinition::create('string');
    $fields['created'] = BaseFieldDefinition::create('created');
    $fields['changed'] = BaseFieldDefinition::create('changed');

    return $fields;
  }
}

