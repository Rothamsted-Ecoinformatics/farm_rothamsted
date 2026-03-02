<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form hook implementations for farm_rothamsted_experiment.
 */
class FormHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_plan_rothamsted_experiment_edit_form_alter')]
  public function formPlanRothamstedExperimentEditFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Alter the name title and description.
    if (isset($form['name']['widget'][0])) {
      $form['name']['widget'][0]['#after_build'][] = 'farm_rothamsted_experiment_alter_plan_name';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_views_exposed_form_alter')]
  public function formViewsExposedFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {

    // Load form state storage and bail if the View is not stored.
    $storage = $form_state->getStorage();
    if (empty($storage['view'])) {
      return;
    }

    // We only want to alter the Views we provide.
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $storage['view'];
    if ($view->id() == 'rothamsted_experiment_plan_plots' && $view->current_display != 'page') {

      // Add column descriptors fieldset.
      $form['column_descriptors'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Column descriptors'),
        '#attached' => [
          'library' => ['farm_rothamsted_experiment/column_descriptors_filters'],
        ],
      ];

      // Move column_descriptor fields to the wrapper.
      // Using #group in the exposed filters does not seem to work.
      foreach ($form as $field_name => $field_value) {
        if (is_array($field_value) && isset($field_value['#group']) && $field_value['#group'] == 'column_descriptors') {
          $form['column_descriptors'][$field_name] = $field_value;
          unset($form[$field_name]);
        }
      }
    }

    // Alter flag filter for experiment plans view.
    if ($view->id() == 'rothamsted_experiment_plans') {

      // If there is no exposed filter for flags, bail.
      if (empty($form['flag_value'])) {
        return;
      }

      // Rewrite flag options.
      $allowed_options = farm_flag_options('plan', ['rothamsted_experiment'], TRUE);
      $form['flag_value']['#options'] = $allowed_options;
    }
  }

}
