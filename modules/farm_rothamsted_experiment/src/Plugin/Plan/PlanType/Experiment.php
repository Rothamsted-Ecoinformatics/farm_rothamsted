<?php

namespace Drupal\farm_rothamsted_experiment\Plugin\Plan\PlanType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\farm_entity\Plugin\Plan\PlanType\FarmPlanType;

/**
 * Provides the experiment plan type.
 *
 * @PlanType(
 *   id = "rothamsted_experiment",
 *   label = @Translation("Experiment"),
 * )
 */
class Experiment extends FarmPlanType {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // Define RRES Experiment Cateogry options.
    $rres_categories_options = [
      'AN' => $this->t('Annual Experiments'),
      'CS' => $this->t('Crop Sequence Experiments'),
      'CL' => $this->t('Classical Experiments'),
      'LTE' => $this->t('Long Term Experiments'),
      'EC' => $this->t('Energy Crop Experiments'),
      'NA' => $this->t('No Proposition Number'),
    ];

    // Build fields using the FarmFieldFactory as possible.
    $field_info = [
      // General fields.
      'experiment_code' => [
        'type' => 'string',
        'label' => $this->t('Experiment Code'),
        'description' => $this->t('The unique identifier for the study, for example 2020/R/CS/790.'),
      ],
      'project_code' => [
        'type' => 'string',
        'label' => $this->t('Project Code'),
        'description' => $this->t('The project code associated with the project.'),
      ],
      'cost_code' => [
        'type' => 'string',
        'label' => $this->t('Cost Code'),
        'description' => $this->t('The cost code associated with the project.'),
      ],
      'location' => [
        'type' => 'string',
        'label' => $this->t('Field Location(s)'),
        'description' => $this->t('The field(s) or location(s) of the experiment.'),
        'multiple' => TRUE,
      ],
      // People fields.
      'principle_investigator' => [
        'type' => 'string',
        'label' => $this->t('Principle Investigator'),
        'description' => $this->t('The lead scientist(s) associated with the experiment.'),
        'multiple' => TRUE,
      ],
      'data_steward' => [
        'type' => 'string',
        'label' => $this->t('Data Steward'),
        'description' => $this->t('The data steward(s) associated with the experiment.'),
        'multiple' => TRUE,
      ],
      'statistician' => [
        'type' => 'string',
        'label' => $this->t('Statistician'),
        'description' => $this->t('The statistician(s) associated with the experiment.'),
      ],
      'primary_contact' => [
        'type' => 'string',
        'label' => $this->t('Primary Contact'),
        'description' => $this->t('The primary contact for this experiment'),
      ],
      'secondary_contact' => [
        'type' => 'string',
        'label' => $this->t('Secondary Contact'),
        'description' => $this->t('The secondary contact for this experiment.'),
      ],
      // Trial design fields.
      'rres_experiment_category' => [
        'type' => 'list_string',
        'label' => $this->t('RRES Experiment Category'),
        'description' => $this->t('The type of experiment.'),
        'List (select one): Annual Experiments, Crop Sequence Experiments, Classical Experiments. Long Term Experiments, Energy Crop Experiments, No Proposition Number' => 1,
        'allowed_values' => $rres_categories_options,
      ],
      'objective' => [
        'type' => 'text_long',
        'label' => $this->t('Objective'),
        'description' => $this->t('The objective(s) of the experiment.'),
      ],
      'plant_type' => [
        'type' => 'entity_reference',
        'label' => $this->t('Crop(s)'),
        'description' => $this->t('The crop(s) planted in the experiment.'),
        'target_type' => 'taxonomy_term',
        'target_bundle' => 'crop_family',
        'auto_create' => FALSE,
        'multiple' => TRUE,
      ],
      // Plan status fields.
      'deviations' => [
        'type' => 'text_long',
        'label' => $this->t('Deviations from plan'),
        'description' => $this->t('Any deviations from the original statistical design.'),
      ],
      'growing_conditions' => [
        'type' => 'text_long',
        'label' => $this->t('Growing Conditions'),
        'description' => $this->t('A description of the growing conditions, where relevant.'),
      ],
      'reason_for_failure' => [
        'type' => 'text_long',
        'label' => $this->t('Reason for Failure'),
        'description' => $this->t('Notes about the cause of crop failure, where relevant.'),
      ],
    ];
    foreach ($field_info as $name => $info) {
      $fields[$name] = $this->farmFieldFactory->bundleFieldDefinition($info);
    }

    /* Create remaining special field types. */

    // Treatment factors.
    $fields['treatment_factors'] = BundleFieldDefinition::create('json_native')
      ->setLabel($this->t('Treatment factors'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Email fields.
    $fields['primary_contact_email'] = BundleFieldDefinition::create('email')
      ->setLabel($this->t('Primary Contact Email'))
      ->setDescription($this->t('The e-mail address of the primary contact.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['secondary_contact_email'] = BundleFieldDefinition::create('email')
      ->setLabel($this->t('Secondary Contact Email'))
      ->setDescription($this->t('The e-mail address of the secondary contact.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Integer year fields.
    $fields['drilling_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Drilling Year'))
      ->setDescription($this->t('The year the experiment is to be drilled.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000);
    $fields['harvest_year'] = BundleFieldDefinition::create('integer')
      ->setLabel($this->t('Harvest Year'))
      ->setDescription($this->t('The year the experiment is to be harvested.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('min', 1800)
      ->setSetting('max', 3000);

    return $fields;
  }

}
