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
  protected function getNewRevisionDefault() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Set the date_year_range for html validation.
    // @see https://www.drupal.org/project/drupal/issues/2942828
    $date_year_range = '1700:+30';
    $datetime_fields = [
      'start' => [
        '#date_year_range' => $date_year_range,
      ],
      'end' => [
        '#date_year_range' => $date_year_range,
      ],
      'public_release_date' => [
        '#date_year_range' => $date_year_range,
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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $entity_type_label = $this->entity->getEntityType()->getSingularLabel();
    $entity_url = $this->entity->toUrl()->setAbsolute()->toString();
    $this->messenger()->addMessage($this->t('Saved %entity_type_label: <a href=":url">%label</a>', ['%entity_type_label' => $entity_type_label, ':url' => $entity_url, '%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl());
    return $status;
  }

}
