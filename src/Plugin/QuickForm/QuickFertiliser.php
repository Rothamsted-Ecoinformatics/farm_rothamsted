<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Fertiliser quick form.
 *
 * @QuickForm(
 *   id = "farm_rothamsted_fertiliser_quick_form",
 *   label = @Translation("Fertiliser"),
 *   description = @Translation("Create fertiliser records."),
 *   helpText = @Translation("Use this form to record feriliser records."),
 *   permissions = {
 *     "create input log",
 *   }
 * )
 */
class QuickFertiliser extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $equipmentGroupNames = ['Tractor Equipment', 'Fertiliser Equipment'];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Load the top level Fertiliser material type.
    $fertiliser_terms = $term_storage->loadByProperties([
      'name' => 'Fertilisers',
      'vid' => 'material_type',
    ]);

    // Build fertiliser options. Default to none, showing message instead.
    $fertiliser_options = [$this->t('No Fertiliser material type configured.')];
    if ($fertiliser_term = reset($fertiliser_terms)) {
      reset($fertiliser_term);
      $fertiliser_children = $term_storage->loadChildren($fertiliser_term->id());
      $fertiliser_options = array_map(function (TermInterface $term) {
        return $term->label();
      }, $fertiliser_children);
    }

    // Fertiliser select list.
    $form['fertiliser'] = [
      '#type' => 'select',
      '#title' => $this->t('Fertiliser'),
      '#options' => $fertiliser_options,
      '#required' => TRUE,
    ];

    // Fertiliser rate.
    $form['rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate of application (kg/ha)'),
      '#field_suffix' => $this->t('kg/ha'),
      '#required' => TRUE,
    ];

    return $form;
  }

}
