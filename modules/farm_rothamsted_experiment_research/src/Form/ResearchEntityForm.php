<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Research entity form class.
 */
class ResearchEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove time element from timestamp fields.
    $datetime_fields = [
      'start' => [
        '#date_time_element' => 'none',
      ],
      'end' => [
        '#date_time_element' => 'none',
      ],
      'public_release_date' => [
        '#date_time_element' => 'none',
      ],
    ];
    foreach ($datetime_fields as $field_id => $field_info) {
      if (isset($form[$field_id]['widget'][0]['value'])) {
        foreach ($field_info as $key => $value) {
          $form[$field_id]['widget'][0]['value'][$key] = $value;
        }
      }
    }

    return $form;
  }

}
