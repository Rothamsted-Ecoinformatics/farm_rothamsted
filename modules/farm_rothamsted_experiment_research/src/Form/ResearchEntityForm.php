<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Research entity form class.
 */
class ResearchEntityForm extends ContentEntityForm {

  /**
   * Function that returns an array of tab definitions to add.
   *
   * @return array
   *   Array of tab definitons keyed by tab id. Each definition should
   *   provide a title, weight and list of fields to include in the tab.
   */
  public function getTabDefinitions() {
    return [];
  }

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

    // Add tabs if specified.
    $tab_definitions = $this->getTabDefinitions();
    if (!empty($tab_definitions)) {

      // Disable HTML5 validation on the form element since it does not work
      // with vertical tabs.
      $form['#attributes']['novalidate'] = 'novalidate';

      // Attach JS to show tabs when there are validation errors.
      // @see QuickExperimentFormBase::buildForm.
      $form['#attached']['library'][] = 'farm_rothamsted_quick/vertical_tab_validation';

      // Create parent for all tabs.
      $form['tabs'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-setup',
      ];

      // Create tabs.
      foreach ($tab_definitions as $tab_id => $tab_info) {
        $tab_id = "tab_$tab_id";
        $form[$tab_id] = [
          '#type' => 'details',
          '#title' => $tab_info['title'],
          '#group' => 'tabs',
        ];

        // Move fields to tabs.
        foreach ($tab_info['fields'] ?? [] as $field_id) {
          $form[$field_id]['#group'] = $tab_id;
        }
      }
    }

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
