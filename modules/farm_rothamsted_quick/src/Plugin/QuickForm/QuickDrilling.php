<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Drilling quick form.
 *
 * @QuickForm(
 *   id = "drilling",
 *   label = @Translation("Drilling"),
 *   description = @Translation("Create drilling records."),
 *   helpText = @Translation("Use this form to record drilling records."),
 *   permissions = {
 *     "create drilling log",
 *   }
 * )
 */
class QuickDrilling extends QuickExperimentFormBase {

  /**
   * {@inheritdoc}
   */
  protected $logType = 'drilling';

  /**
   * {@inheritdoc}
   */
  protected $parentLogCategoryName = 'Drilling categories';

  /**
   * {@inheritdoc}
   */
  protected $tractorField = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $machineryGroupNames = ['Drilling Equipment'];

  /**
   * {@inheritdoc}
   */
  protected bool $productsTab = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Drilling tab.
    $drilling = [
      '#type' => 'details',
      '#title' => $this->t('Drilling'),
      '#group' => 'tabs',
      '#weight' => 0,
    ];

    // Additional information tab.
    $additional = [
      '#type' => 'details',
      '#title' => $this->t('Additional information'),
      '#group' => 'tabs',
      '#weight' => 1,
    ];

    // Crop count.
    $crop_counts = range(1, 10);
    $drilling['crop_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many crops?'),
      '#options' => array_combine($crop_counts, $crop_counts),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'cropsCallback'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-crops',
      ],
    ];

    // Crops wrapper.
    $drilling['crops'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'farm-rothamsted-crops',
      ],
      '#tree' => TRUE,
    ];

    // Add fields for each nutrient.
    $crop_count = $form_state->get('crop_count') ?? 1;
    if (($trigger = $form_state->getTriggeringElement()) && NestedArray::getValue($trigger['#array_parents'], [1]) == 'crop_count') {
      $crop_count = (int) $trigger['#value'];
    }
    $form_state->set('crop_count', $crop_count);
    for ($i = 0; $i < $crop_count; $i++) {

      // Fieldset for each product.
      $drilling['crops'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Crop @number', ['@number' => $i + 1]),
        '#collapsible' => TRUE,
        '#open' => TRUE,
      ];

      // Crop wrapper.
      $crop_wrapper = $this->buildInlineWrapper();

      // Get values from form state.
      $variety_options = [];
      $crop_type_id = NULL;

      // If the crop_type changed, get the new value for this delta.
      if (($trigger = $form_state->getTriggeringElement())
          && NestedArray::getValue($trigger['#array_parents'], [2]) == $i
          && NestedArray::getValue($trigger['#array_parents'], [4]) == 'crop_type') {
        if ($crop_type_id = $trigger['#value']) {
          $variety_options = $this->getTermTreeOptions('plant_type', $crop_type_id);
        }
      }
      // Else get the previous product_type from form state.
      elseif ($crop_type_id = $form_state->get(['crops', $i, 'crop_wrapper', 'crop_type'])) {
        $variety_options = $this->getTermTreeOptions('plant_type', $crop_type_id);
      }

      // Always save the crop_type to form state.
      $form_state->set(['crops', $i, 'crop_wrapper', 'crop_type'], $crop_type_id);

      // Crop type.
      $crop_type_options = $this->getTermTreeOptions('plant_type', 0, 1);
      $crop_wrapper['crop_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Crop'),
        '#description' => $this->t('A list of different crop types. The list can be expanded or amended in the plant types taxonomy.'),
        '#options' => $crop_type_options,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'cropVarietyCallback'],
          'event' => 'change',
          'wrapper' => "crop-$i-wrapper",
        ],
      ];

      // Variety.
      $crop_wrapper['variety'] = [
        '#type' => 'select',
        '#title' => $this->t('Variety'),
        '#description' => $this->t('The variety(s) being planted. To select more than one option on a desktop PC hold down the CTRL button on and select multiple.'),
        '#options' => $variety_options,
        '#multiple' => TRUE,
        '#required' => TRUE,
        '#prefix' => "<div id='crop-$i-wrapper'>",
        '#suffic' => '</div',
      ];

      // Assign the wrapper to the form.
      $drilling['crops'][$i]['crop_wrapper'] = $crop_wrapper;
    }

    // Target plant population units options.
    $target_plant_population_units_options = [
      'plants/m2' => 'plants/m2',
      '%' => '%',
    ];

    // Seed rate.
    $seed_rate_units_options = [
      'seeds/m2' => 'seeds/m2',
      'plants/ha' => 'plants/ha',
    ];
    $seed_rate = [
      'title' => $this->t('Seed rate'),
      'description' => $this->t('The number of seeds drilled per unit area. This is an agronomic decision based on the crop, the season and the growing conditions.'),
      'measure' => ['#value' => 'rate'],
      'units' => ['#options' => $seed_rate_units_options],
      'required' => TRUE,
    ];
    $drilling['seed_rate'] = $this->buildQuantityField($seed_rate);

    // Drilling rate.
    $drilling_rate_units_options = [
      'kg/ha' => 'kg/ha',
      'units/ha' => 'units/ha',
    ];
    $drilling['drilling_rate'] = $this->buildQuantityField([
      'title' => $this->t('Drilling rate'),
      'description' => $this->t('The volume of seed drilled per unit area. This information must be provided as it is essential information for scientists wanting to analyse the crop data.'),
      'measure' => ['#value' => 'rate'],
      'units' => ['#options' => $drilling_rate_units_options],
      'required' => TRUE,
    ]);

    // Seed dressings.
    $seed_dressing_options = $this->getChildTermOptionsByName('material_type', 'Seed Dressings');
    $drilling['seed_dressing'] = [
      '#type' => 'select',
      '#title' => $this->t('Seed dressing(s)'),
      '#description' => $this->t("Please record the seed dressings applied either by the farm or by the supplier. You can expand this list by adding additional products under 'Seed Dressings' on the Material Types taxonomy."),
      '#options' => $seed_dressing_options,
      '#multiple' => TRUE,
    ];

    // Seed labels.
    $drilling['seed_labels'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Seed labels'),
      '#description' => $this->t('Photograph(s) of the seed label taken prior to drilling or confirm the right seed batch and variety was used.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validImageExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
      '#required' => TRUE,
    ];

    // Add the drilling tab and fields to the form.
    $form['drilling'] = $drilling;

    // Thousand grain weight.
    $additional['thousand_grain_weight'] = $this->buildQuantityField([
      'title' => $this->t('Thousand grain weight (TGW)'),
      'description' => $this->t('The average weight of 1,000 grains.'),
      'measure' => ['#value' => 'weight'],
      'units' => ['#value' => 'g'],
    ]);

    // Germination rate.
    $additional['germination_rate'] = $this->buildQuantityField([
      'title' => $this->t('Seed Germination Test Result'),
      'description' => $this->t('The germination rate of the seed batch, measured by placing 50 to 100 seeds in a sealed tupperware box lined with wet kitchen roll and counting the number of seeds germinated after 10 - 14 days.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#value' => '%'],
    ]);

    // Target plant population.
    $target_plant_population = [
      'title' => $this->t('Target plant population'),
      'description' => $this->t('The target population for plant establishment after drilling.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#options' => $target_plant_population_units_options],
    ];
    $additional['target_plant_population'] = $this->buildQuantityField($target_plant_population);

    // Establishment average.
    $establishment_average_units_options = [
      'plants/m2' => 'plants/m2',
      '%' => '%',
    ];
    $establishment_average = [
      'title' => $this->t('Establishment average'),
      'description' => $this->t('The estimated plant establishment after drilling as a percentage. This is usually based on previous field records over the last 2- 5 years.'),
      'measure' => ['#value' => 'ratio'],
      'units' => ['#options' => $establishment_average_units_options],
    ];
    $additional['establishment_average'] = $this->buildQuantityField($establishment_average);

    // Drilling depth.
    $additional['drilling_depth'] = $this->buildQuantityField([
      'title' => $this->t('Drilling depth'),
      'description' => $this->t('The estimate of the depth at which the seed was drilled. It is important to take this info account when reviewing establishment avarages.'),
      'measure' => ['#value' => 'length'],
      'units' => ['#value' => 'cm'],
    ]);

    // Seed lineage.
    $additional['seed_lineage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seed lineage'),
      '#description' => $this->t('The plant asset(s) which the seed came from.'),
    ];

    // Add the additional information tab and fields to the form.
    $form['additional'] = $additional;

    // Move recommendation fields to products applied tab.
    foreach (['recommendation_number', 'recommendation_files'] as $field_name) {
      $form['products'][$field_name] = $form['setup'][$field_name];
      unset($form['setup'][$field_name]);
    }

    return $form;
  }

  /**
   * Crops ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The crops render array.
   */
  public function cropsCallback(array &$form, FormStateInterface $form_state) {
    return $form['drilling']['crops'];
  }

  /**
   * Crop variety ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The products render array.
   */
  public function cropVarietyCallback(array &$form, FormStateInterface $form_state) {
    // Get the triggering element to return the correct crop offset.
    $target_crop = $form_state->getTriggeringElement();
    $target_crop_offset = $target_crop['#parents'][1];
    return $form['drilling']['crops'][$target_crop_offset]['crop_wrapper']['variety'];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLog(array $form, FormStateInterface $form_state): array {
    $log = parent::prepareLog($form, $form_state);

    // Add the crops and varieties to the drilling log plant_type.
    $plant_types = [];
    $crop_count = $form_state->getValue('crop_count');
    for ($i = 0; $i < $crop_count; $i++) {
      if ($crop = $form_state->getValue(['crops', $i, 'crop_wrapper', 'crop_type'])) {
        $plant_types[] = $crop;
      }
      if ($varieties = $form_state->getValue(['crops', $i, 'crop_wrapper', 'variety'])) {
        $varieties = (array) $varieties;
        $plant_types = array_merge($plant_types, array_values($varieties));
      }
    }
    $log['plant_type'] = array_unique($plant_types);

    // Add the drilling log seed_dressing.
    $log['seed_dressing'] = $form_state->getValue('seed_dressing');

    return $log;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {

    // Aggregate all crop and variety names.
    $crop_names = [];
    $variety_names = [];
    $crop_count = $form_state->getValue('crop_count');
    for ($i = 0; $i < $crop_count; $i++) {
      if ($crop = $form_state->getValue(['crops', $i, 'crop_wrapper', 'crop_type'])) {
        $crop_names[] = $this->entityTypeManager->getStorage('taxonomy_term')->load($crop)->label();
      }
      if ($varieties = $form_state->getValue(['crops', $i, 'crop_wrapper', 'variety'])) {
        $varieties = (array) $varieties;
        foreach ($varieties as $variety) {
          $variety_names[] = $this->entityTypeManager->getStorage('taxonomy_term')->load($variety)->label();
        }
      }
    }

    // Generate the log name.
    $name_parts = [
      'prefix' => 'Drilling: ',
      'crop' => implode(', ', $crop_names),
      'variety' => ' (' . implode(', ', $variety_names) . ')',
    ];
    $priority_keys = ['prefix', 'crop', 'variety'];
    return $this->prioritizedString($name_parts, $priority_keys, 255, '...)');
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    array_push(
      $field_keys,
      'seed_rate',
      'drilling_rate',
      'thousand_grain_weight',
      'germination_rate',
      'target_plant_population',
      'establishment_average',
      'drilling_depth',
    );
    return parent::getQuantities($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getImageIds(array $field_keys, FormStateInterface $form_state) {
    $field_keys[] = 'seed_labels';
    return parent::getImageIds($field_keys, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareNotes(array $note_fields, FormStateInterface $form_state): array {
    // Prepend additional note fields.
    array_unshift(
      $note_fields,
      ...[
        [
          'key' => 'seed_lineage',
          'label' => $this->t('Seed lineage'),
        ],
      ]
    );
    return parent::prepareNotes($note_fields, $form_state);
  }

}
