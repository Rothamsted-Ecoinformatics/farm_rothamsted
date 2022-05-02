<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Asset\AssetType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\farm_entity\Plugin\Asset\AssetType\FarmAssetType;
use Drupal\field\Entity\FieldStorageConfig;

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
      'plot_id' => [
        'type' => 'string',
        'label' => $this->t('Plot ID'),
        'required' => TRUE,
      ],
      'block' => [
        'type' => 'string',
        'label' => $this->t('Block'),
      ],
    ];
    foreach ($field_info as $name => $info) {
      $fields[$name] = $this->farmFieldFactory->bundleFieldDefinition($info);
    }

    /* Create remaining special field types. */
    $fields['serial'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Serial'))
      ->setDescription($this->t('Numeric integer unique to each plot.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['column'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Column'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    $fields['row'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Row'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    $fields['treatment_factors'] = BundleFieldDefinition::create('key_value')
      ->setLabel($this->t('Treatment factors'))
      ->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE);

    return $fields;
  }

}
