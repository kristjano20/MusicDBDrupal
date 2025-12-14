<?php

namespace Drupal\music_db\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\music_db\SongAccessControlHandler;
use Drupal\music_db\SongListBuilder;
use Drupal\music_db\Form\SongDeleteForm;
use Drupal\music_db\Form\SongForm;

#[ContentEntityType(
  id: 'song',
  label: new TranslatableMarkup('Song'),

  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
    # 'revision' => 'revision_id',
  ],

  handlers: [
    'access' => SongAccessControlHandler::class,
    'list_builder' => SongListBuilder::class,
    'form' => [
      'add' => SongForm::class,
      'edit' => SongForm::class,
      'delete' => SongDeleteForm::class,
    ],
  ],
  links: [
    'canonical' => '/song/{song}',
    'add-form' => '/song/add',
    'edit-form' => '/song/{song}/edit',
    'delete-form' => '/song/{song}/delete',
    'collection' => '/admin/content/songs',
  ],
  admin_permission: 'administer song entities',

  base_table: 'song',

  # revision_table: 'song_revision'
)]
class Song extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Duration'))
      ->setDescription(t('Song duration in mm:ss format (e.g. 03:45).'))
      ->setSettings(['max_length' => 10])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['artist'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Artist'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['album'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Album'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['track_no'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Track number'))
      ->setDescription(t('Track number on the album.'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['spotify_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spotify ID'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['discogs_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Discogs ID'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('view', TRUE);

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
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
