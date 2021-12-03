<?php

namespace Drupal\farm_rothamsted\Plugin\Log\LogType;

use Drupal\farm_entity\Plugin\Log\LogType\FarmLogType;

/**
 * Provides the drilling log type.
 *
 * @LogType(
 *   id = "drilling",
 *   label = @Translation("Drilling"),
 * )
 */
class Drilling extends FarmLogType {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // Add the plant_type field to drilling logs.
    $field_info = [
      'plant_type' => [
        'type' => 'entity_reference',
        'label' => $this->t('Crop/variety'),
        'description' => "Enter this crop/variety drilled.",
        'target_type' => 'taxonomy_term',
        'target_bundle' => 'plant_type',
        'auto_create' => FALSE,
        'required' => TRUE,
        'multiple' => TRUE,
        'weight' => [
          'form' => -90,
          'view' => -90,
        ],
      ],
    ];

    foreach ($field_info as $name => $info) {
      $fields[$name] = $this->farmFieldFactory->bundleFieldDefinition($info);
    }
    return $fields;
  }

}
