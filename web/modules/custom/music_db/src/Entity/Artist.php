<?php

namespace Drupal\music_db\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\music_db\ArtistAccessControlHandler;
use Drupal\Core\music_db\ArtistListBuilder;
use Drupal\Core\music_db\Form\ArtistDeleteForm;
use Drupal\Core\music_db\Form\ArtistForm;

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
    ;

    $fields['date_of_birth'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of Birth/Formation'))
    ;

    $fields['date_of_death'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of Death/Disband'))
    ;

    $fields['photo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Photo of Artist'))
    ;

    $fields['about'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('About'))
    ;

    $fields['spotify_id'] = BaseFieldDefinition::create('string');
    $fields['discogs_id'] = BaseFieldDefinition::create('string');
    $fields['created'] = BaseFieldDefinition::create('created');
    $fields['changed'] = BaseFieldDefinition::create('changed');

    return $fields;
  }
}

