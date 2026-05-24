<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research\Plugin\Action\Derivative;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an action deriver that finds entity types with a entity status form.
 */
class EntityStatusChangeDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->getApplicableEntityTypes() as $entity_type_id => $entity_type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $entity_type_id;
        $definition['label'] = new TranslatableMarkup('Change status');
        $definition['confirm_form_route_name'] = 'entity.' . $entity_type->id() . '.entity_status_action_form';
        $definitions[$entity_type_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return $this->derivatives;
  }

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->hasLinkTemplate('entity-status-action-form');
  }

}
