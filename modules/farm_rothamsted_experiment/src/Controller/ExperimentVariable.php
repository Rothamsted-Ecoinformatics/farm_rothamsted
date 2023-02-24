<?php

namespace Drupal\farm_rothamsted_experiment\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\plan\Entity\PlanInterface;

/**
 * Experiment variables page.
 */
class ExperimentVariable extends ControllerBase {

  /**
   * Variables page.
   *
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The plan.
   */
  public function variables(PlanInterface $plan) {

    // Add button if no variables exist.
    if ($plan->get('column_descriptors')->isEmpty()) {
      $url = Url::fromRoute('farm_rothamsted_experiment.experiment.variable_form', ['plan' => $plan->id()])->setAbsolute()->toString();
      $message = $this->t('No experiment variables have been uploaded. <a href=":url">Add variables</a>', [':url' => $url]);
      $this->messenger()->addWarning($message);
      return [];
    }
    $render = $plan->get('column_descriptors')->view([
      'type' => 'column_descriptors_tables',
      'label' => 'visually_hidden',
      'settings' => [],
    ]);
    $render['#title'] = $this->t('Experiment variables');
    return $render;
  }

  /**
   * Experimental variables list page.
   *
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The plan.
   */
  public function variablesList(PlanInterface $plan) {

    $raw_columns = $plan->get('column_descriptors')->first()->value;
    $columns = Json::decode($raw_columns);

    $items = [];
    foreach ($columns as $column) {
      $items[$column['column_id']] = [
        'title' => $column['column_name'],
        'description' => $column['column_description'],
        'url' => Url::fromRoute('entity.plan.collection'),
        'localized_options' => [],
      ];
    }

    $render = [
      '#theme' => 'admin_block_content',
      '#content' => $items,
    ];

    return $render;
  }

}
