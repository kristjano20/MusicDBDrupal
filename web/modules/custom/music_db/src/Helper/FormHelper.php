<?php

namespace Drupal\music_db\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class FormHelper {

  /**
   * Extracts provider value from form state or form array.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param object|null $entity*
   * @return string
   */
  public static function getProviderValue(array $form, FormStateInterface $form_state, $entity = NULL): string {
    $submitted = $form_state->getValue(['autocomplete_provider', 0, 'value'])
      ?: $form_state->getValue(['autocomplete_provider', 'value'])
      ?: $form_state->getValue('autocomplete_provider');

    if ($submitted && is_string($submitted)) {
      return $submitted;
    }

    if ($entity && !$entity->isNew() && $entity->hasField('autocomplete_provider')) {
      $value = $entity->get('autocomplete_provider')->value;
      if ($value) {
        return $value;
      }
    }

    $provider_element = Element::children($form, 'autocomplete_provider');
    if (!empty($provider_element)) {
      $field_key = reset($provider_element);
      if (isset($form['autocomplete_provider'][$field_key]['widget'][0]['value']['#default_value'])) {
        return $form['autocomplete_provider'][$field_key]['widget'][0]['value']['#default_value'];
      }
    }

    if (isset($form['autocomplete_provider']['widget'][0]['value']['#default_value'])) {
      return $form['autocomplete_provider']['widget'][0]['value']['#default_value'];
    }
    if (isset($form['autocomplete_provider']['#default_value'])) {
      return $form['autocomplete_provider']['#default_value'];
    }

    return 'none';
  }

  /**
   * Gets the name field element from form.
   * @param array $form
   * @return array|null
   */
  public static function &getNameField(array &$form) {
    $name_children = Element::children($form, 'name');
    if (!empty($name_children)) {
      $widget_key = reset($name_children);
      if (isset($form['name'][$widget_key][0]['value'])) {
        return $form['name'][$widget_key][0]['value'];
      }
    }
    if (isset($form['name']['widget'][0]['value'])) {
      return $form['name']['widget'][0]['value'];
    }
    if (isset($form['name'])) {
      return $form['name'];
    }
    $null = NULL;
    return $null;
  }

  /**
   * sets autocomplete on name field based on provider.
   * @param array $form
   * @param string $provider
   */
  public static function setAutocompleteOnNameField(array &$form, string $provider): void {
    $name_field = &self::getNameField($form);
    if (!$name_field) {
      return;
    }

    if ($provider !== 'none') {
      $route_name = ($provider === 'discogs')
        ? 'music_db.discogs_artist_autocomplete'
        : 'music_db.spotify_artist_autocomplete';
      $description = ($provider === 'discogs')
        ? t('Start typing to search Discogs and pick the correct artist.')
        : t('Start typing to search Spotify and pick the correct artist.');

      $name_field['#autocomplete_route_name'] = $route_name;
      $name_field['#autocomplete_route_parameters'] = [];
      $name_field['#description'] = $description;
    }
    else {
      unset($name_field['#autocomplete_route_name']);
      unset($name_field['#autocomplete_route_parameters']);
      $name_field['#description'] = t('Enter the artist name.');
    }
  }

  /**
   * Sets AJAX on provider field.
   * @param array $form
   */
  public static function setAjaxOnProviderField(array &$form): void {
    $ajax_config = [
      'callback' => 'music_db_autocomplete_provider_ajax_callback',
      'wrapper' => 'artist-name-wrapper',
      'event' => 'change',
      'effect' => 'fade',
      'progress' => ['type' => 'throbber'],
    ];

    if (isset($form['autocomplete_provider']['widget'][0]['value'])) {
      $form['autocomplete_provider']['widget'][0]['value']['#ajax'] = $ajax_config;
    }
    elseif (isset($form['autocomplete_provider'])) {
      $form['autocomplete_provider']['#ajax'] = $ajax_config;
    }
  }

  /**
   * Wraps name field for AJAXcreplacement.
   * @param array $form
   */
  public static function wrapNameField(array &$form): void {
    $name_field = &self::getNameField($form);
    if (!$name_field) {
      return;
    }

    if (!isset($name_field['#prefix'])) {
      $name_field['#prefix'] = '<div id="artist-name-wrapper">';
    }
    if (!isset($name_field['#suffix'])) {
      $name_field['#suffix'] = '</div>';
    }
  }

}

