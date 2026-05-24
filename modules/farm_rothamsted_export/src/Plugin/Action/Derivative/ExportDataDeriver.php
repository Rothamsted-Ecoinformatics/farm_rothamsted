<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_export\Plugin\Action\Derivative;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an action deriver for the export data action.
 *
 * @see \Drupal\farm_rothamsted_export\Plugin\Action\ExportData
 */
class ExportDataDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->getApplicableEntityTypes() as $entity_type_id => $entity_type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $entity_type_id;
        $definition['label'] = new TranslatableMarkup('Export data');
        $definition['confirm_form_route_name'] = 'entity.' . $entity_type->id() . '.export_data_action_form';
        $definitions[$entity_type_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return in_array($entity_type->id(), [
      'asset',
      'log',
      'quantity',
      'plan',
      'rothamsted_researcher',
      'rothamsted_design',
      'rothamsted_experiment',
      'rothamsted_program',
      'rothamsted_proposal',
    ]);
  }

}
