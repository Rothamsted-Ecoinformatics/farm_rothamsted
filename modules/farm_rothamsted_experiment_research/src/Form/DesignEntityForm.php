<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Design entity form class.
 */
class DesignEntityForm extends ResearchEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getTabDefinitions() {
    return [
      'description' => [
        'title' => $this->t('Description'),
        'weight' => 0,
        'fields' => [
          'experiment',
          'name',
          'description',
          'start',
          'end',
          'design_changes',
        ],
      ],
      'rotation' => [
        'title' => $this->t('Rotation'),
        'weight' => 5,
        'fields' => [
          'previous_cropping',
          'rotation_treatment',
          'add_rotation',
          'rotation_name',
          'rotation_description',
          'rotation_crop',
          'rotation_phasing',
          'rotation_notes',
        ],
      ],
      'layout' => [
        'title' => $this->t('In-Field Layout'),
        'weight' => 10,
        'fields' => [
          'layout_description',
          'plot_non_standard',
          'total_plot_area',
          'experiment_area',
        ],
      ],
      'statistical_design' => [
        'title' => $this->t('Statistical Design'),
        'weight' => 15,
        'fields' => [
          'objective',
          'num_treatments',
          'treatment',
          'dependent_variables',
          'hypothesis',
          'blocking_structure',
          'statistical_design',
          'blocking_constraint',
          'model',
          'num_factor_level_combinations',
          'unequal_replication',
          'num_replicates',
          'statistician',
          'notes',
        ],
      ],
      'restriction' => [
        'title' => $this->t('Restrictions'),
        'weight' => 20,
        'fields' => [
          'restriction_crop',
          'restriction_crop_desc',
          'restriction_gm',
          'restriction_gm_desc',
          'restriction_ge',
          'restriction_ge_desc',
          'restriction_off_label',
          'restriction_off_label_desc',
          'restriction_licence_perm',
          'restriction_licence_perm_desc',
          'restriction_physical',
          'restriction_physical_desc',
          'restriction_other',
        ],
      ],
      'mgmt_seed' => [
        'title' => $this->t('Seed'),
        'weight' => 25,
        'fields' => [
          'mgmt_seed_treatments',
          'mgmt_seed_provision',
          'mgmt_variety_notes',
        ],
      ],
      'mgmt_cultivation' => [
        'title' => $this->t('Cultivation'),
        'weight' => 25,
        'fields' => [
          'mgmt_ploughing',
          'mgmt_levelling',
          'mgmt_seed_cultivation',
        ],
      ],
      'mgmt_planting' => [
        'title' => $this->t('Planting'),
        'weight' => 25,
        'fields' => [
          'mgmt_planting_date',
          'mgmt_seed_rate',
          'mgmt_drilling_rate',
          'mgmt_drill_spacing',
          'mgmt_plant_estab',
        ],
      ],
      'mgmt_spraying' => [
        'title' => $this->t('Spraying'),
        'weight' => 25,
        'fields' => [
          'mgmt_fungicide',
          'mgmt_herbicide',
          'mgmt_insecticide',
          'mgmt_nematicide',
          'mgmt_molluscicide',
          'mgmt_pgr',
        ],
      ],
      'mgmt_irrigation' => [
        'title' => $this->t('Irrigation'),
        'weight' => 25,
        'fields' => [
          'mgmt_irrigation',
        ],
      ],
      'mgmt_nutrition' => [
        'title' => $this->t('Nutrition'),
        'weight' => 25,
        'fields' => [
          'mgmt_organic_amendments',
          'mgmt_nitrogen',
          'mgmt_potassium',
          'mgmt_phosphorous',
          'mgmt_magnesium',
          'mgmt_sulphur',
          'mgmt_micronutrients',
          'mgmt_ph',
        ],
      ],
      'mgmt_harvest' => [
        'title' => $this->t('Harvest'),
        'weight' => 25,
        'fields' => [
          'mgmt_grain_harvest',
          'mgmt_straw_harvest',
          'mgmt_other_harvest',
        ],
      ],
      'mgmt_post_harvest' => [
        'title' => $this->t('Post-harvest'),
        'weight' => 25,
        'fields' => [
          'mgmt_post_harvest',
          'mgmt_post_harvest_interval',
        ],
      ],
      'mgmt_other' => [
        'title' => $this->t('Other'),
        'weight' => 25,
        'fields' => [
          'mgmt_other',
        ],
      ],
      'file' => [
        'title' => $this->t('Files'),
        'weight' => 28,
        'fields' => [
          'file',
          'image',
          'link',
        ],
      ],
      'status' => [
        'title' => $this->t('Status'),
        'weight' => 30,
        'fields' => [
          'status',
          'status_notes',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Add field to add a rotation.
    $has_rotation = !$this->entity->get('rotation_name')->isEmpty();
    $form['add_rotation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add rotation'),
      '#description' => $this->t('If the design is a rotation for the experiment please define the rotation below.'),
      '#default_value' => $has_rotation,
      // This weight works to render below Rotation treatment but is fragile.
      '#weight' => 40,
    ];

    $form = parent::form($form, $form_state);

    // Require revision log message.
    if (isset($form['revision_log_message']['widget'][0]['value'])) {
      $form['revision_log_message']['widget'][0]['value']['#required'] = TRUE;
    }

    // Create parent tab for management sub-tabs.
    // Sub-tabs will be rendered as details elements within the management tab.
    $management_tab = 'tab_management';
    $form[$management_tab] = [
      '#type' => 'details',
      '#title' => $this->t('Management'),
      '#group' => 'tabs',
      '#weight' => 25,
    ];
    $management_tabs = [
      'seed',
      'cultivation',
      'planting',
      'spraying',
      'irrigation',
      'nutrition',
      'harvest',
      'post_harvest',
      'other',
    ];
    foreach ($management_tabs as $tab_id) {
      $tab_id = "tab_mgmt_$tab_id";
      $form[$tab_id]['#group'] = $management_tab;
    }

    // Hide rotation fields when the rotation is a treatment.
    $rotation_fields = [
      'rotation_name',
      'rotation_description',
      'rotation_crop',
      'rotation_phasing',
      'rotation_notes',
    ];
    foreach ($rotation_fields as $field) {
      $form[$field]['#states'] = [
        'visible' => [
          ':input[name="add_rotation"]' => ['checked' => TRUE],
        ],
      ];
    }

    // Disable number of replicates if unequal replication.
    $form['num_replicates']['#states'] = [
      'disabled' => [
        ':input[name="unequal_replication"]' => ['value' => 1],
      ],
    ];

    // Hide restriction description fields until checked.
    $rotation_fields = [
      'restriction_crop',
      'restriction_gm',
      'restriction_ge',
      'restriction_off_label',
      'restriction_licence_perm',
      'restriction_physical',
    ];
    foreach ($rotation_fields as $field) {
      $form[$field . '_desc']['#states'] = [
        'visible' => [
          ':input[name="' . $field . '"]' => ['value' => 1],
        ],
      ];
    }

    // Add ajax to update the statistical design field after changing the
    // selected blocking structure.
    $form['statistical_design']['#attributes']['id'] = 'statistical-design-wrapper';
    $form['blocking_structure']['widget']['#ajax'] = [
      'callback' => [$this, 'blockingStructureCallback'],
      'wrapper' => 'statistical-design-wrapper',
      'event' => 'change',
    ];

    // Build options from the selected blocking structure.
    // Check if a value has been set to the form state storage (not form state
    // values) via AJAX before using entity's current field value.
    // This prevents errors from happening when other elements reset the form
    // state, like file uploads. See issue #616.
    $blocking_structure = $form_state->get('blocking_structure') ?? $this->entity->get('blocking_structure')->value;
    if (($trigger = $form_state->getTriggeringElement()) && NestedArray::getValue($trigger['#array_parents'], [0]) == 'blocking_structure') {
      $blocking_structure = $trigger['#value'];
    }
    // Save the blocking structure to form state storage.
    $form_state->set('blocking_structure', $blocking_structure);

    // Load the statistical design options.
    $options = [];
    if ($blocking_structure) {
      $options = farm_rothamsted_experiment_research_statistical_design_options($blocking_structure);
    }
    $form['statistical_design']['widget']['#options'] = $options;

    return $form;
  }

  /**
   * AJAX callback for blocking structure and design.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to replace.
   */
  public function blockingStructureCallback(array &$form, FormStateInterface $form_state) {
    return $form['statistical_design'];
  }

}
