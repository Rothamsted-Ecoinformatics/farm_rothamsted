<?php

namespace Drupal\farm_rothamsted_quick\Traits;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper functions for building quick form quantity fields.
 */
trait QuickQuantityFieldTrait {

  use StringTranslationTrait;

  /**
   * Helper function to build a render array for a quantity field.
   *
   * @param array $config
   *   Configuration for the quantity field.
   *
   * @return array
   *   Render array for the quantity field.
   */
  public function buildQuantityField(array $config = []) {

    // Default the label to the fieldset title.
    if (!empty($config['title']) && empty($config['label'])) {
      $config['label']['#value'] = (string) $config['title'];
    }

    // Auto-hide fields if #value is provided and no #type is specified.
    foreach (['measure', 'value', 'units', 'label'] as $field_name) {
      if (isset($config[$field_name]['#value']) && !isset($config[$field_name]['#type'])) {
        $config[$field_name]['#type'] = 'hidden';
      }
    }

    // Auto-populate the unit #options if the #value is specified.
    if (isset($config['units']['#value']) && empty($config['units']['#options'])) {
      $default_unit = $config['units']['#value'];
      $config['units']['#options'] = [$default_unit => $default_unit];
    }

    // Default config.
    $default_config = [
      'border' => FALSE,
      'measure' => [
        '#type' => 'select',
        '#title' => $this->t('Measure'),
        '#options' => quantity_measure_options(),
        '#weight' => 0,
      ],
      'value' => [
        '#type' => 'number',
        '#weight' => 5,
        '#min' => 0,
        '#step' => 0.01,
      ],
      'units' => [
        '#weight' => 10,
      ],
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#weight' => 15,
        '#size' => 15,
      ],
    ];
    $config = array_replace_recursive($default_config, $config);

    // Start a render array with a fieldset.
    $render = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#theme_wrappers' => ['fieldset'],
      '#attributes' => [
        'class' => ['inline-quantity', 'container-inline'],
      ],
      '#attached' => [
        'library' => ['farm_rothamsted_quick/quantity_fieldset'],
      ],
    ];

    // Configure the top level fieldset.
    foreach (['title', 'description'] as $key) {
      if (!empty($config[$key])) {
        $render["#$key"] = $config[$key];
      }
    }

    // Include each quantity subfield.
    $render['measure'] = $config['measure'];
    $render['value'] = $config['value'];
    $render['label'] = $config['label'];

    // Save units to a variable for now.
    // The key may be saved as units or units_id.
    $units_key_name = 'units';
    $units = $config['units'];

    // Check if unit options are provided.
    if (!empty($units['#options'])) {
      $units_options = $units['#options'];

      // If a numeric value is provided, assume these are term ids.
      if (is_numeric(key($units_options))) {
        $units_key_name = 'units_id';
      }

      // Render the units as select options.
      $units += [
        '#type' => 'select',
        '#options' => $units_options,
      ];

      // If the unit value is hard-coded add a field suffix to the value field
      // with the first option label.
      if (isset($units['#value'])) {
        $render['value']['#field_suffix'] = current($units_options);
      }
    }
    // Else default to entity_autocomplete unit terms. Use the units_id key.
    else {
      $units_key_name = 'units_id';
      // Add entity_autocomplete.
      $units += [
        '#type' => 'entity_autocomplete',
        '#placeholder' => $this->t('Units'),
        '#target_type' => 'taxonomy_term',
        '#selection_handler' => 'default',
        '#selection_settings' => [
          'target_bundles' => ['unit'],
        ],
        '#tags' => FALSE,
        '#size' => 15,
      ];
    }

    // Include units in render array.
    $render[$units_key_name] = $units;

    // Check if the quantity is required.
    if (!empty($config['required'])) {
      $render['#required'] = TRUE;
      $render['value']['#required'] = TRUE;
    }

    // Remove the border if needed.
    if (empty($config['border'])) {
      $render['#attributes']['class'][] = 'no-border';
    }

    return $render;
  }

}
