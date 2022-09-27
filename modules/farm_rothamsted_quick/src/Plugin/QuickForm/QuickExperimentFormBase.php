<?php

namespace Drupal\farm_rothamsted_quick\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\farm_group\GroupMembershipInterface;
use Drupal\farm_location\AssetLocationInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_quick\Traits\QuickPrepopulateTrait;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
use Drupal\farm_rothamsted_quick\Traits\QuickQuantityFieldTrait;
use Drupal\farm_rothamsted_quick\Traits\QuickTaxonomyOptionsTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for experiment plan quick forms.
 */
abstract class QuickExperimentFormBase extends QuickFormBase {

  use QuickFileTrait;
  use QuickLogTrait;
  use QuickPrepopulateTrait;
  use QuickQuantityFieldTrait;
  use QuickTaxonomyOptionsTrait;

  /**
   * Constant for specifying the required product batch number.
   */
  const PRODUCT_BATCH_NUM_REQUIRED = 'required';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership service.
   *
   * @var \Drupal\farm_group\GroupMembershipInterface
   */
  protected $groupMembership;

  /**
   * The asset location service.
   *
   * @var \Drupal\farm_location\AssetLocationInterface
   */
  protected $assetLocation;

  /**
   * ID of log type the quick form creates.
   *
   * @var string
   */
  protected $logType;

  /**
   * Name of the parent log category to use.
   *
   * @var string|bool
   */
  protected $parentLogCategoryName = FALSE;

  /**
   * Boolean indicating if the quick form should have a tractor field.
   *
   * @var bool
   */
  protected $tractorField = FALSE;

  /**
   * The machinery equipment group names to use.
   *
   * @var string[]
   */
  protected $machineryGroupNames = [];

  /**
   * Boolean indication if the quick form should have a products applied tab.
   *
   * @var bool
   */
  protected bool $productsTab = FALSE;

  /**
   * Minimum number of products for products tab.
   *
   * @var int
   */
  protected int $productsMinimum = 0;

  /**
   * Value indicating to include the products applied batch num field.
   *
   * This value can also be set to 'required' if the batch number can be
   * required.
   *
   * @var bool|string
   */
  protected $productBatchNum = FALSE;

  /**
   * Default values to use when initializing the form.
   *
   * @var array
   */
  protected $defaultValues = [];

  /**
   * Constructs a QuickFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\farm_group\GroupMembershipInterface $group_membership
   *   The group membership service.
   * @param \Drupal\farm_location\AssetLocationInterface $asset_location
   *   The asset location service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager, GroupMembershipInterface $group_membership, AssetLocationInterface $asset_location) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
    $this->entityTypeManager = $entity_type_manager;
    $this->groupMembership = $group_membership;
    $this->assetLocation = $asset_location;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('group.membership'),
      $container->get('asset.location'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // First build default values from the request.
    $this->buildDefaults(\Drupal::request());

    // Define base quick form tabs.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-setup',
    ];

    // Disable HTML5 validation on the form element since it does not work
    // with vertical tabs.
    $form['#attributes']['novalidate'] = 'novalidate';

    // Attach JS to show tabs when there are validation errors.
    $form['#attached']['library'][] = 'farm_rothamsted_quick/vertical_tab_validation';

    // Setup tab.
    $setup = [
      '#type' => 'details',
      '#title' => $this->t('Setup'),
      '#group' => 'tabs',
      '#weight' => -10,
    ];

    // Products applied tab.
    $products = [
      '#type' => 'details',
      '#title' => $this->t('Products applied'),
      '#group' => 'tabs',
      '#weight' => 5,
    ];

    // Operation tab.
    $operation = [
      '#type' => 'details',
      '#title' => $this->t('Operation'),
      '#group' => 'tabs',
      '#weight' => 10,
    ];

    // Job status tab.
    $status = [
      '#type' => 'details',
      '#title' => $this->t('Job Status'),
      '#group' => 'tabs',
      '#weight' => 15,
    ];

    // Load prepopulated assets.
    $assets = $this->defaultValues['asset'] ?? $this->getPrepopulatedEntities('asset', $form_state);

    // Asset field.
    $setup['asset'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Asset'),
      '#description' => $this->t('The plant asset that this log relates to. Search by field locations above.'),
      '#required' => TRUE,
      '#prefix' => '<div id="asset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Plot asset(s) already selected. You can only select plant or plot assets.
    if (!empty($assets)) {
      $setup['asset']['#description'] = $this->t('The plant asset that this log relates to. These are prepopulated and cannot be changed. Start a new quick form to select individual assets.');
      $setup['asset']['#disabled'] = TRUE;
      $setup['asset']['#default_value'] = array_keys($assets);
    }
    // Else add a field to search for plant assets by location.
    else {

      // Location.
      $setup['location'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Location'),
        '#description' => $this->t('The field in which the asset is planted. If the area is not present in the list it can be added as a new land asset.'),
        '#target_type' => 'asset',
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'rothamsted_quick_location_reference',
            'display_name' => 'entity_reference',
            'arguments' => [],
          ],
          'match_operator' => 'CONTAINS',
        ],
        '#tags' => TRUE,
        '#required' => TRUE,
        '#weight' => -100,
        '#ajax' => [
          'callback' => [$this, 'assetCallback'],
          'wrapper' => 'asset-wrapper',
          'event' => 'autocompleteclose',
        ],
      ];

      // Load an existing location from form storage.
      $location_ids = $form_state->get('location') ?? [];
      if (($trigger = $form_state->getTriggeringElement()) && NestedArray::getValue($trigger['#array_parents'], [1]) == 'location') {
        $location_ids = array_column($form_state->getValue('location', []), 'target_id');
      }
      $form_state->set('location', $location_ids);

      // Get asset options if there are location ids.
      if (!empty($location_ids)) {

        // Start a query for active plant or experiment_boundary land assets
        // in the selected location(s).
        $asset_query = $this->entityTypeManager->getStorage('asset')->getQuery()
          ->condition('status', 'archived', '!=');

        // Limit to plant or experiment_boundary land assets.
        $experiment_land_type = $asset_query->andConditionGroup()
          ->condition('type', 'land')
          ->condition('land_type', 'experiment_boundary');
        $asset_type = $asset_query->orConditionGroup()
          ->condition('type', 'plant')
          ->condition($experiment_land_type);
        $asset_query->condition($asset_type);

        // Add an or condition group.
        // Include assets that are children of the selected location.
        $logic = $asset_query->orConditionGroup()
          ->condition('parent', $location_ids, 'IN')
          ->condition('parent.entity:asset.parent', $location_ids, 'IN')
          ->condition('parent.entity:asset.parent.entity:asset.parent', $location_ids, 'IN');

        // Query assets moved to the selected location.
        // Include assets moved to sub-locations of the selected location.
        $location_asset_query = $this->entityTypeManager->getStorage('asset')->getQuery()
          ->condition('status', 'archived', '!=');
        $location_condition = $location_asset_query->orConditionGroup()
          ->condition('id', $location_ids, 'IN')
          ->condition('parent', $location_ids, 'IN')
          ->condition('parent.entity:asset.parent', $location_ids, 'IN')
          ->condition('parent.entity:asset.parent.entity:asset.parent', $location_ids, 'IN');
        $location_asset_query->condition($location_condition);
        $location_ids = $location_asset_query->execute();
        $locations = $this->entityTypeManager->getStorage('asset')->loadMultiple($location_ids);

        /** @var \Drupal\farm_location\AssetLocationInterface $service */
        $service = \Drupal::service('asset.location');
        $assets = $service->getAssetsByLocation($locations);

        // @todo Remove this mapping if location interface changes.
        // @see https://github.com/farmOS/farmOS/pull/565
        $asset_location_ids = array_map(function (AssetInterface $asset) {
          return $asset->id();
        }, $assets);
        if (!empty($asset_location_ids)) {
          $logic->condition('id', $asset_location_ids, 'IN');
        }

        // Include logic or group.
        $asset_query->condition($logic);

        // Get assets.
        $asset_ids = $asset_query->execute();
        $assets = $this->entityTypeManager->getStorage('asset')->loadMultiple($asset_ids);
      }
    }

    // Add asset options.
    $asset_options = array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $assets);
    $setup['asset']['#options'] = $asset_options;

    // Add log category field if specified.
    if (!empty($this->parentLogCategoryName)) {

      // Add field.
      $setup['log_category'] = [
        '#type' => 'select',
        '#title' => $this->t('Log category'),
        '#required' => TRUE,
        '#multiple' => TRUE,
      ];

      // Query for the parent term.
      $parent_category_ids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->condition('vid', 'log_category')
        ->condition('status', TRUE)
        ->condition('name', $this->parentLogCategoryName)
        ->execute();

      // Build options from parent term.
      if ($parent_category_id = reset($parent_category_ids)) {
        $category_options = $this->getTermTreeOptions('log_category', $parent_category_id);
      }
      // Disable and state the category does not exist.
      else {
        $category_options = [$this->t('Log category does not exist: %term', ['%term' => $this->parentLogCategoryName])];
        $setup['log_category']['#disabled'] = TRUE;
      }

      // Add the options.
      $setup['log_category']['#options'] = $category_options;
    }

    // Operation time.
    $setup['time'] = $this->buildInlineWrapper();

    // Scheduled date and time.
    $setup['time']['timestamp'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start date and time'),
      '#description' => $this->t('The start date and time of the operation.'),
      '#default_value' => new DrupalDateTime(),
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#date_year_range' => '-15:+15',
    ];

    // Tractor hours start.
    $setup['time']['tractor_hours_start'] = $this->buildQuantityField([
      'title' => $this->t('Tractor hours (start)'),
      'description' => $this->t('The number of tractor hours displayed at the start of the job.'),
      'measure' => ['#value' => 'count'],
      'units' => ['#value' => 'hours'],
      'required' => TRUE,
    ]);

    // Equipment wrapper.
    $setup['equipment_wrapper'] = $this->buildInlineWrapper();

    // Build the tractor field if required.
    if ($this->tractorField) {
      $tractor_options = $this->getGroupMemberOptions(['Tractor Equipment'], ['equipment']);
      $setup['equipment_wrapper']['tractor'] = [
        '#type' => 'select',
        '#title' => $this->t('Tractor'),
        '#description' => $this->t('Select the tractor used for this operation. You can expand the list by assigning Equipment Assets to the group "Tractor Equipment".'),
        '#options' => $tractor_options,
        '#default_value' => $this->defaultValues['tractor'] ?? NULL,
        '#required' => TRUE,
      ];
    }

    // Build the machinery field if required.
    if (!empty($this->machineryGroupNames)) {
      $equipment_options = $this->getGroupMemberOptions($this->machineryGroupNames, ['equipment']);
      $machinery_options_string = implode(",", $this->machineryGroupNames);
      $setup['equipment_wrapper']['machinery'] = [
        '#type' => 'select',
        '#title' => $machinery_options_string,
        '#description' => $this->t('Select the equipment used for this operation. You can expand the list by assigning Equipment Assets to the group "@group_names". To select more than one hold down the CTRL button and select multiple.', ['@group_names' => $machinery_options_string]),
        '#options' => $equipment_options,
        '#default_value' => $this->defaultValues['machinery'] ?? NULL,
        '#multiple' => TRUE,
        '#required' => TRUE,
      ];
    }

    // Equipment settings.
    $setup['equipment_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Equipment Settings'),
      '#description' => $this->t('An option to include any notes on the specific equipment settings used.'),
      '#default_value' => $this->defaultValues['notes']['Equipment Settings'] ?? NULL,
    ];

    // Recommendation Number - text - optional.
    $setup['recommendation_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation Number'),
      '#description' => $this->t('A recommendation or reference number from the agronomist or crop consultant.'),
    ];

    // Recommendation files.
    $setup['recommendation_files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Recommendation files'),
      '#description' => $this->t('A PDF, word or excel file with the agronomist or crop consultant recommendations.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'file'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validFileExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Include the setup tab.
    $form['setup'] = $setup;

    // Product count.
    $product_values = range($this->productsMinimum, 10);
    $products['product_count'] = [
      '#type' => 'select',
      '#title' => $this->t('How many products?'),
      '#options' => array_combine($product_values, $product_values),
      '#default_value' => $this->productsMinimum,
      '#ajax' => [
        'callback' => [$this, 'productsCallback'],
        'event' => 'change',
        'wrapper' => 'farm-rothamsted-products',
      ],
    ];

    // Only build the products tab if needed.
    if ($this->productsTab) {
      $products['products'] = [
        '#prefix' => '<div id="farm-rothamsted-products">',
        '#suffix' => '</div>',
      ];

      // Add fields for each nutrient.
      $products['products']['#tree'] = TRUE;
      $product_count = $form_state->get('product_count') ?? $this->productsMinimum;
      if (($trigger = $form_state->getTriggeringElement()) && NestedArray::getValue($trigger['#array_parents'], [1]) == 'product_count') {
        $product_count = (int) $trigger['#value'];
      }
      $form_state->set('product_count', $product_count);
      for ($i = 0; $i < $product_count; $i++) {

        // Fieldset for each product.
        $products['products'][$i] = [
          '#type' => 'details',
          '#title' => $this->t('Product @number', ['@number' => $i + 1]),
          '#collapsible' => TRUE,
          '#open' => TRUE,
        ];

        // Product wrapper.
        $product_wrapper = $this->buildInlineWrapper();

        // Get values from form state.
        $product_options = [];
        $product_type_id = NULL;

        // If the product_type changed, get the new value for this delta.
        if (($trigger = $form_state->getTriggeringElement())
            && NestedArray::getValue($trigger['#array_parents'], [2]) == $i
            && NestedArray::getValue($trigger['#array_parents'], [4]) == 'product_type') {
          $product_type_id = $trigger['#value'];
          $product_options = $this->getTermTreeOptions('material_type', $product_type_id);
        }
        // Else get the previous product_type from form state.
        elseif ($product_type_id = $form_state->get(['products', $i, 'product_wrapper', 'product_type'])) {
          $product_options = $this->getTermTreeOptions('material_type', $product_type_id);
        }

        // Always save the product_type to form state.
        $form_state->set(['products', $i, 'product_wrapper', 'product_type'], $product_type_id);

        // Product type.
        $product_type_options = $this->getTermTreeOptions('material_type', 0, 1);
        $product_wrapper['product_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Product type'),
          '#description' => $this->t('A list of different product types (manure, compost, fertiliser, etc). The list can be expanded or amended in the inputs taxonomy.'),
          '#options' => $product_type_options,
          '#required' => TRUE,
          '#ajax' => [
            'callback' => [$this, 'productTypeCallback'],
            'event' => 'change',
            'wrapper' => "product-$i-wrapper",
          ],
        ];

        // Product.
        $product_wrapper['product'] = [
          '#type' => 'select',
          '#title' => $this->t('Product'),
          '#description' => $this->t('The product used.'),
          '#options' => $product_options,
          '#required' => TRUE,
          '#prefix' => "<div id='product-$i-wrapper'>",
          '#suffic' => '</div',
        ];
        $products['products'][$i]['product_wrapper'] = $product_wrapper;

        // Product application rate units.
        $application_rate_units_options = $this->getChildTermOptionsByName('unit', 'Volume per unit area');
        $product_application_rate = [
          'title' => $this->t('Product rate'),
          'measure' => ['#value' => 'rate'],
          'units' => ['#options' => $application_rate_units_options],
          'required' => TRUE,
        ];
        $products['products'][$i]['product_rate'] = $this->buildQuantityField($product_application_rate);

        // Include the product batch number if needed.
        if ($this->productBatchNum !== FALSE) {
          $products['products'][$i]['batch_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Product batch number'),
            '#description' => $this->t('The unique product batch number, as provided by the product manufacturer.'),
          ];

          // Make the product batch number required if specified.
          if ($this->productBatchNum === self::PRODUCT_BATCH_NUM_REQUIRED) {
            $products['products'][$i]['batch_number']['#required'] = TRUE;
            $products['products'][$i]['batch_number']['#description'] .= ' ' . $this->t('If there is no batch number available, please write NA.');
          }
        }
      }

      // Product labels.
      $products['product_labels'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Product labels'),
        '#description' => $this->t('Please photograph the product labels where relevant.'),
        '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
        '#upload_validators' => [
          'file_validate_extensions' => self::$validImageExtensions,
        ],
        '#multiple' => TRUE,
        '#extended' => TRUE,
      ];

      // Include the products applied tab.
      $form['products'] = $products;
    }

    // Time taken.
    $operation['time'] = $this->buildInlineWrapper();
    $operation['time']['#weight'] = -10;
    $operation['time']['time_taken']['#tree'] = TRUE;
    $hour_options = range(0, 12);
    $operation['time']['time_taken']['hours'] = [
      '#type' => 'select',
      '#title' => $this->t('Time taken: Hours'),
      '#options' => array_combine($hour_options, $hour_options),
      '#required' => TRUE,
    ];
    $minute_options = range(0, 45, 15);
    $operation['time']['time_taken']['minutes'] = [
      '#type' => 'select',
      '#title' => $this->t('Minutes'),
      '#options' => array_combine($minute_options, $minute_options),
      '#required' => TRUE,
    ];

    // Tractor hours end.
    $operation['time']['tractor_hours_end'] = $this->buildQuantityField([
      'title' => $this->t('Tractor hours (end)'),
      'description' => $this->t('The number of tractor hours displayed at the emd of the job.'),
      'measure' => ['#value' => 'count'],
      'units' => ['#value' => 'hours'],
      'required' => TRUE,
    ]);

    // Fuel use.
    $fuel_use_units_options = [
      'l' => 'l',
      'gal' => 'gal',
    ];
    $fuel_use = [
      'title' => $this->t('Fuel use'),
      'description' => $this->t('The amount of fuel used.'),
      'measure' => ['#value' => 'volume'],
      'units' => ['#options' => $fuel_use_units_options],
      'border' => FALSE,
    ];
    $operation['fuel_use'] = $this->buildQuantityField($fuel_use);
    $operation['fuel_use']['#weight'] = 10;

    // Photographs wrapper.
    $operation['photographs'] = $this->buildInlineWrapper();
    $operation['photographs']['#weight'] = 15;

    // Crop Photographs.
    $operation['photographs']['crop_photographs'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crop Photograph(s)'),
      '#description' => $this->t('A photograph of the crop, if applicable.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validImageExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Photographs of paper records.
    $operation['photographs']['photographs_of_paper_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Photographs of paper record(s)'),
      '#description' => $this->t('One or more photographs of any paper records, if applicable.'),
      '#upload_location' => $this->getFileUploadLocation('log', $this->logType, 'image'),
      '#upload_validators' => [
        'file_validate_extensions' => self::$validImageExtensions,
      ],
      '#multiple' => TRUE,
      '#extended' => TRUE,
    ];

    // Log notes.
    $operation['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#description' => $this->t('Any additional notes.'),
      '#weight' => 20,
    ];

    // Include the operation tab.
    $form['operation'] = $operation;

    // General job status fields.
    $status['general'] = $this->buildInlineWrapper();

    // Operator field.
    $operator_options = $this->getUserOptions(['farm_operator']);
    $status['general']['owner'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#description' => $this->t('The operator(s) who carried out the task.'),
      '#options' => $operator_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    // Job status.
    $status_options = [
      'done' => $this->t('Done'),
      'pending' => $this->t('Pending'),
    ];
    $status['general']['job_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Job status'),
      '#description' => $this->t('The current status of the job.'),
      '#options' => $status_options,
      '#required' => TRUE,
    ];

    // Flags.
    $flag_options = farm_flag_options('log', [$this->logType]);
    $status['general']['flag'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag'),
      '#description' => $this->t('Flag this job if it is a priority, requires monitoring or review.'),
      '#options' => $flag_options,
      '#empty_option' => $this->t('Select a flag'),
    ];

    // Include the job status tab.
    $form['job_status'] = $status;

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Asset ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The products render array.
   */
  public function assetCallback(array &$form, FormStateInterface $form_state) {
    return $form['setup']['asset'];
  }

  /**
   * Products applied ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The products render array.
   */
  public function productsCallback(array &$form, FormStateInterface $form_state) {
    return $form['products']['products'];
  }

  /**
   * Products applied product type ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The products render array.
   */
  public function productTypeCallback(array &$form, FormStateInterface $form_state) {
    // Get the triggering element to return the correct product offset.
    $target_product = $form_state->getTriggeringElement();
    $target_product_offset = $target_product['#parents'][1];
    return $form['products']['products'][$target_product_offset]['product_wrapper']['product'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // This method should be overridden by subclasses. The following only
    // exists to provide an example.
    // First build and array of log information.
    $log = $this->prepareLog($form, $form_state);

    // Finally, create the log.
    $this->createLog($log);
  }

  /**
   * Helper function to build form defaults.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  protected function buildDefaults(Request $request) {

    // Build common defaults if a log is provided.
    if ($log_id = $request->get('log')) {

      // Save the log.
      $log = $this->entityTypeManager->getStorage('log')->load($log_id);
      $this->defaultValues['log'] = $log;

      // Assets.
      $this->defaultValues['asset'] = $log->get('asset')->referencedEntities();

      // Equipment assets.
      $equipment = $log->get('equipment')->referencedEntities();
      $equipment_ids = array_map(function (AssetInterface $asset) {
        return $asset->id();
      }, $equipment);

      // Tractor.
      $tractor_options = $this->getGroupMemberOptions(['Tractor Equipment'], ['equipment']);
      $this->defaultValues['tractor'] = array_intersect($equipment_ids, array_keys($tractor_options));

      // Machinery.
      $machinery_options = $this->getGroupMemberOptions($this->machineryGroupNames, ['equipment']);
      $this->defaultValues['machinery'] = array_intersect($equipment_ids, array_keys($machinery_options));

      // Notes.
      $this->defaultValues['notes'] = [];
      if (($notes = $log->get('notes')->value) && $lines = explode(PHP_EOL, $notes)) {
        foreach ($lines as $line) {
          if (($parts = explode(':', $line)) && count($parts) == 2) {
            $this->defaultValues['notes'][$parts[0]] = trim($parts[1]);
          }
        }
      }
    }
  }

  /**
   * Helper function to load group members of a given asset type.
   *
   * @param string[] $group_names
   *   The group names to query.
   * @param string[] $asset_types
   *   The asset types to limit group members to.
   *
   * @return array
   *   An array of asset labels keyed by the asset ID.
   */
  protected function getGroupMemberOptions(array $group_names, array $asset_types = []): array {
    $asset_storage = $this->entityTypeManager->getStorage('asset');

    // Load the groups.
    $group_ids = $asset_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('type', 'group')
      ->condition('name', $group_names, 'IN')
      ->execute();

    // Bail if there are no groups.
    if (empty($group_ids)) {
      return [];
    }

    // Load the group members.
    $groups = $asset_storage->loadMultiple($group_ids);
    $group_members = $this->groupMembership->getGroupMembers($groups);

    // If specified, filter group members to a single asset type.
    if (!empty($asset_type)) {
      $group_members = array_filter($group_members, function (AssetInterface $asset) use ($asset_types) {
        return in_array($asset->getEntityTypeId(), $asset_types);
      });
    }

    // Build group options.
    $group_options = array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $group_members);
    natsort($group_options);

    return $group_options;
  }

  /**
   * Helper function to build a sorted option list of users in role(s).
   *
   * @param array $roles
   *   Limit to users of the specified roles.
   *
   * @return array
   *   An array of user labels indexed by user id and sorted alphabetically.
   */
  protected function getUserOptions(array $roles = []): array {

    // Query active, non-admin users.
    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->condition('uid', '1', '>');

    // Limit to specified roles.
    if (!empty($roles)) {
      $query->condition('roles', $roles, 'IN');
    }

    // Load users.
    $user_ids = $query->execute();
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);

    // Build user options.
    $user_options = array_map(function (UserInterface $user) {
      return $user->label();
    }, $users);
    natsort($user_options);

    return $user_options;
  }

  /**
   * Helper function to build an inline wrapper container.
   *
   * @return array
   *   An inline wrapper render array.
   */
  protected function buildInlineWrapper(): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'style' => ['display: flex; flex-wrap: wrap; column-gap: 2em;'],
      ],
    ];
  }

  /**
   * Helper function to prepare an array of data for creating a log.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of log data.
   *
   * @see \Drupal\farm_quick\Traits\QuickLogTrait::createLog()
   */
  protected function prepareLog(array $form, FormStateInterface $form_state): array {

    // Start an array of log data to pass to QuickLogTrait::createLog.
    $assets = $form_state->getValue('asset');
    $log = [
      'type' => $this->logType,
      'status' => $form_state->getValue('job_status'),
      'name' => $this->getLogName($form, $form_state),
      'timestamp' => $form_state->getValue('timestamp')->getTimestamp(),
      'asset' => $assets,
      'flag' => $form_state->getValue('flag'),
      'owner' => $form_state->getValue('owner'),
      'category' => $form_state->getValue('log_category', []),
    ];

    // Copy the current asset locations to the log.
    $log['location'] = $this->getAssetLocation(array_column($assets, 'target_id'));

    // Add equipment references.
    $tractor = $form_state->getValue('tractor');
    $machinery = array_filter($form_state->getValue('machinery'));
    $log['equipment'] = [...$machinery, $tractor];

    // Quantities.
    $quantity_keys = ['tractor_hours_start', 'tractor_hours_end', 'fuel_use'];
    $log['quantity'] = $this->getQuantities($quantity_keys, $form_state);

    // Files.
    $file_fields = ['recommendation_files'];
    $log['file'] = $this->getFileIds($file_fields, $form_state);

    // Images.
    $image_fields = [
      'crop_photographs',
      'photographs_of_paper_records',
      'product_labels',
    ];
    $log['image'] = $this->getImageIds($image_fields, $form_state);

    // Notes.
    // Define note fields to add.
    $note_fields = [];
    $note_fields[] = [
      'key' => 'recommendation_number',
      'label' => $this->t('Recommendation Number'),
    ];

    // Add products applied product batch numbers to the notes field.
    // These will be formatted like: {Product name} Batch Number: Value.
    if ($this->productsTab && $this->productBatchNum && $product_count = $form_state->getValue('product_count')) {
      for ($i = 0; $i < $product_count; $i++) {
        if ($material = $form_state->getValue(['products', $i, 'product_wrapper', 'product'])) {
          $material_term = Term::load($material);
          $note_fields[] = [
            'key' => ['products', $i, 'batch_number'],
            'label' => $material_term->label() . ' Batch Number',
          ];
        }
      }
    }

    $note_fields[] = [
      'key' => 'equipment_settings',
      'label' => $this->t('Equipment Settings'),
    ];
    $note_fields[] = [
      'key' => 'notes',
      'label' => $this->t('Additional notes'),
    ];

    // Prepare notes and split onto separate lines.
    $log['notes'] = $this->prepareNotes($note_fields, $form_state);

    return $log;
  }

  /**
   * Helper function to build the log name.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string
   *   The log name.
   */
  protected function getLogName(array $form, FormStateInterface $form_state): string {
    // Subclasses should override this method.
    return 'Quick form log name';
  }

  /**
   * Helper function to prepare an array of form fields for the log notes.
   *
   * @param array $note_fields
   *   An array of arrays defining form field keys and labels.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The value to assign to the log notes field.
   */
  protected function prepareNotes(array $note_fields, FormStateInterface $form_state): array {

    // Start an array of note strings.
    $notes = [];

    // Build note string.
    foreach ($note_fields as $field_info) {
      $key = $field_info['key'] ?? NULL;
      if (!empty($key) && $form_state->hasValue($key) && !$form_state->isValueEmpty($key)) {
        $note_value = $form_state->getValue($key);
        // Separate array values with commas.
        if (is_array($note_value)) {
          $note_value = implode(', ', $note_value);
        }
        $notes[] = $field_info['label'] . ': ' . $note_value;
      }
    }

    // Split notes onto separate lines.
    return [
      'value' => implode(PHP_EOL, $notes),
      'format' => 'default',
    ];
  }

  /**
   * Helper function to get quantities to reference in the log quantity field.
   *
   * This function should be implemented by quick form subclasses that provide
   * additional quantities.
   *
   * @param array $field_keys
   *   The quantity form field keys to include.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of quantity values that will be used to create quantities.
   *
   * @see QuickQuantityFieldTrait::buildQuantityField()
   * @see \Drupal\farm_quick\Traits\QuickQuantityTrait::createQuantity()
   */
  protected function getQuantities(array $field_keys, FormStateInterface $form_state): array {
    $quantities = [];

    // Add time taken quantity.
    if ($time_taken = $form_state->getValue('time_taken')) {
      $hours = $time_taken['hours'];
      $minutes = $time_taken['minutes'];
      $quantities[] = [
        'label' => (string) $this->t('Time taken'),
        'value' => $hours + $minutes / 60,
        'measure' => 'time',
        'units' => 'hours',
      ];
    }

    // Add products applied rate material quantities.
    if ($this->productsTab && $product_count = $form_state->get('product_count')) {
      for ($i = 0; $i < $product_count; $i++) {
        $material = $form_state->getValue(['products', $i, 'product_wrapper', 'product']);
        $quantity = $form_state->getValue(['products', $i, 'product_rate']);
        $quantity['type'] = 'material';
        $quantity['material_type'] = $material;
        $quantities[] = $quantity;
      }
    }

    // Get quantity values for each group of quantity fields.
    foreach ($field_keys as $field_key) {

      // Get submitted value.
      $quantity = $form_state->getValue($field_key);

      // Ensure the quantity is an array and has a numeric value.
      if (is_array($quantity) && is_numeric($quantity['value'])) {
        $quantities[] = $quantity;
      }
    }

    return $quantities;
  }

  /**
   * Helper function to get file entity ids to reference in the log file field.
   *
   * This function should be implemented by quick form subclasses that provide
   * additional files.
   *
   * @param array $field_keys
   *   The form field keys to include.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of file entity ids.
   */
  protected function getFileIds(array $field_keys, FormStateInterface $form_state) {
    return $this->getSubmittedFileIds($field_keys, $form_state);
  }

  /**
   * Helper function to get file entity ids to reference in the log image field.
   *
   * This function should be implemented by quick form subclasses that provide
   * additional images.
   *
   * @param array $field_keys
   *   The form field keys to include.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of file entity ids.
   */
  protected function getImageIds(array $field_keys, FormStateInterface $form_state) {
    return $this->getSubmittedFileIds($field_keys, $form_state);
  }

  /**
   * Helper function to get file entity ids from managed_file form elements.
   *
   * @param array $field_keys
   *   The form field keys to include.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of file entity ids.
   */
  protected function getSubmittedFileIds(array $field_keys, FormStateInterface $form_state) {

    // Collect the uploaded file ids for each form field.
    $file_ids = array_map(function ($field_key) use ($form_state) {

      // Get submitted value.
      $value = $form_state->getValue($field_key);

      // If multiple files are uploaded than use the 'fids' key.
      $fids = [];
      if (!empty($value) && is_array($value)) {
        $fids = $value['fids'] ?? $value;
      }
      return $fids;
    }, $field_keys);

    // Merge file ids into a single array.
    return array_merge(...$file_ids);
  }

  /**
   * Helper function to get the location of assets.
   *
   * @param array $asset_ids
   *   The asset ids to get location of.
   *
   * @return array
   *   Array of location asset ids.
   */
  protected function getAssetLocation(array $asset_ids): array {

    // Start an array of location ids.
    $final_location_ids = [];

    // Get the submitted assets.
    $assets = $this->entityTypeManager->getStorage('asset')->loadMultiple($asset_ids);
    if (!empty($assets)) {

      // Get the location of each asset and collect all location ids.
      array_walk_recursive($assets, function (AssetInterface $asset) use (&$final_location_ids) {
        $location_ids = array_map(function (AssetInterface $asset) {
          return $asset->id();
        }, $this->assetLocation->getLocation($asset));
        array_push($final_location_ids, ...$location_ids);
      });
    }

    // Return the unique location ids.
    return array_unique($final_location_ids);
  }

}
