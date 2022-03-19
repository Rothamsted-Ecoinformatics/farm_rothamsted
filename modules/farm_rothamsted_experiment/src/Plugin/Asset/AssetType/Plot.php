<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Asset\AssetType;

use Drupal\farm_entity\Plugin\Asset\AssetType\FarmAssetType;

/**
 * Provides the plot asset type.
 *
 * @AssetType(
 *   id = "plot",
 *   label = @Translation("Plot"),
 * )
 */
class Plot extends FarmAssetType {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // Add the plant_type field to plot assets.
    $field_info = [
      'plant_type' => [
        'type' => 'entity_reference',
        'label' => $this->t('Crop'),
        'description' => "Enter this plot asset's crop.",
        'target_type' => 'taxonomy_term',
        'target_bundle' => 'plant_type',
        'auto_create' => TRUE,
        'required' => FALSE,
        'multiple' => TRUE,
      ],
    ];

    foreach ($field_info as $name => $info) {
      $fields[$name] = $this->farmFieldFactory->bundleFieldDefinition($info);
    }
    return $fields;
  }

}
