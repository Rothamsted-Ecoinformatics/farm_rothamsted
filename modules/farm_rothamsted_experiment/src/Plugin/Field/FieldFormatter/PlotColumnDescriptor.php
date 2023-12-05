<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Plugin implementation of the plot_column_descriptor formatter.
 *
 * @FieldFormatter(
 *   id = "plot_column_descriptor",
 *   label = @Translation("Plot column descriptor"),
 *   field_types = {
 *     "key_value",
 *     "key_value_long",
 *   }
 * )
 */
class PlotColumnDescriptor extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['column_id'] = '';
    $settings['column_levels'] = [];
    $settings['raw'] = FALSE;
    $settings['value_only'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Get the default textfield form.
    $form = parent::settingsForm($form, $form_state);

    // Limit which column_id is shown.
    $form['column_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column ID'),
      '#description' => $this->t('Only display column descriptors for a given column ID.'),
      '#default_value' => $this->getSetting('column_id'),
      '#weight' => 2,
    ];

    // Column descriptors.
    $form['column_levels'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column levels'),
      '#description' => $this->t('Column level mapping. Only to be used programmatically.'),
      '#default_value' => $this->getSetting('column_levels'),
      '#weight' => 2,
      '#disabled' => TRUE,
    ];

    // Display raw values.
    $form['raw'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Raw values'),
      '#default_value' => $this->getSetting('raw'),
      '#description' => $this->t('Display the raw column level ID instead of the column level name.'),
      '#weight' => 3,
    ];

    // Allow the formatter to hide the key.
    $form['value_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Value only'),
      '#default_value' => $this->getSetting('value_only'),
      '#description' => $this->t('Make the formatter hide the "Key" part of the field and display the "Value" only.'),
      '#weight' => 4,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $key = $this->getSetting('value_only') ? '' : ' [Key] : ';

    // Add a summary for the key field.
    $summary[] = $this->t('Display format: @key [Value].', ['@key' => $key]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // Get the value elements from the TextDefaultFormatter class.
    $value_elements = parent::viewElements($items, $langcode);

    // Buffer the return value.
    $elements = [];

    $column_levels = $this->getSetting('column_levels');
    if (empty($column_levels)) {

    }

    // Loop through all items.
    foreach ($items as $delta => $item) {

      // Skip items that don't match the configured key value.
      if (!empty($this->getSetting('column_id')) && $item->key != $this->getSetting('column_id')) {
        continue;
      }

      // Just add the key element to the render array, when 'value_only' is not
      // checked.
      if (!$this->getSetting('value_only')) {
        $elements[$delta]['key'] = [
          '#plain_text' => nl2br($item->key . ' : '),
        ];
      }
      // Add the value to the render array.
      if ($this->getSetting('raw')) {
        $elements[$delta]['value'] = $value_elements[$delta];
      }
      else {
        if (isset($column_levels[(int) $item->value - 1])) {
          $elements[$delta]['value'] = [
            '#plain_text' => $column_levels[(int) $item->value - 1],
          ];
        }
      }

    }
    return $elements;
  }

}
