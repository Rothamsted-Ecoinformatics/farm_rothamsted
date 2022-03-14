<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\farm_group\GroupMembershipInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_quick\Traits\QuickPrepopulateTrait;
use Drupal\farm_rothamsted\Traits\QuickFileTrait;
use Drupal\farm_rothamsted\Traits\QuickQuantityFieldTrait;
use Drupal\farm_rothamsted\Traits\QuickTaxonomyOptionsTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Psr\Container\ContainerInterface;

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
   * ID of log type the quick form creates.
   *
   * @var string
   */
  protected $logType;

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
   * Boolean indicating to include the products applied batch num field.
   *
   * @var bool
   */
  protected bool $productBatchNum = FALSE;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager, GroupMembershipInterface $group_membership) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
    $this->entityTypeManager = $entity_type_manager;
    $this->groupMembership = $group_membership;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Define base quick form tabs.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-setup',
    ];

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
    // The autocomplete_deluxe element expects the default value to
    // be the "Asset name (id), Asset 2 (id)".
    $default_assets = $this->getPrepopulatedEntities('asset');
    $default_asset_value = implode(', ', array_map(function ($asset) {
      return $asset->label() . ' (' . $asset->id() . ')';
    }, $default_assets));

    // Build the URL for the autocomplete_deluxe endpoint.
    // See autocomplete_deluxe README.md.
    $selection_settings = [
      'target_bundles' => ['plot', 'plant'],
    ];
    $target_type = 'asset';
    $selection_handler = 'default:asset';
    $data = serialize($selection_settings) . $target_type . $selection_handler;
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $route_parameters = [
      'target_type' => 'asset',
      'selection_handler' => 'default:asset',
      'selection_settings_key' => $selection_settings_key,
    ];
    $url = Url::fromRoute('autocomplete_deluxe.autocomplete', $route_parameters, ['absolute' => TRUE])->getInternalPath();

    // The selection settings must be saved in the keyvalue store.
    // autocomplete_deluxe extends core entity_autocomplete which does the
    // same thing.
    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    // Asset field.
    $setup['asset'] = [
      '#type' => 'autocomplete_deluxe',
      '#title' => $this->t('Plant asset(s)'),
      '#description' => $this->t('The asset that this log relates to. For experiments always specify the plot numbers when applying treatments. To add additional plant assets, begin typing the name of the asset and select from the list.'),
      '#autocomplete_deluxe_path' => $url,
      '#target_type' => 'asset',
      '#multiple' => TRUE,
      '#default_value' => $default_asset_value,
      '#required' => TRUE,
    ];

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
        '#description' => $this->t('Select the equipment  used for this operation. You can expand the list by assigning Equipment Assets to the group ":group_names". To select more than one hold down the CTRL button and select multiple.', [':group_names' => $machinery_options_string]),
        '#options' => $equipment_options,
        '#multiple' => TRUE,
        '#required' => TRUE,
      ];
    }

    // Equipment settings.
    $setup['equipment_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Equipment Settings'),
      '#description' => $this->t('An option to include any notes on the specific equipment settings used.'),
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
    // @todo We need AJAX to populate multiple of these.
    $products['product_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a product'),
      '#options' => array_combine(range(1, 5), range(1, 5)),
      '#default_value' => 1,
      '#ajax' => [
        'callback' => [$this, 'productsCallback'],
        'even' => 'change',
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
      $product_count = $form_state->getValue('product_count', 1);
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
        $product_options = [];
        if ($product_type_id = $form_state->getValue(['products', $i, 'product_wrapper', 'product_type'])) {
          $product_options = $this->getTermTreeOptions('material_type', $product_type_id);
        }
        $product_wrapper['product'] = [
          '#type' => 'select',
          '#title' => $this->t('Product'),
          '#description' => $this->t('The product used.'),
          '#options' => $product_options,
          '#required' => TRUE,
          '#validated' => TRUE,
          '#prefix' => "<div id='product-$i-wrapper'>",
          '#suffic' => '</div',
        ];
        $products['products'][$i]['product_wrapper'] = $product_wrapper;

        // Product application rate.
        $rate_units = [
          'ml' => 'ml',
          'l' => 'l',
          'g' => 'g',
          'kg' => 'kg',
        ];
        $product_application_rate = [
          'title' => $this->t('Product rate'),
          'measure' => ['#value' => 'rate'],
          'units' => ['#options' => $rate_units],
          'required' => TRUE,
        ];
        $products['products'][$i]['product_rate'] = $this->buildQuantityField($product_application_rate);

        // Include the product batch number if needed.
        if ($this->productBatchNum) {
          $products['products'][$i]['batch_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Product batch number'),
            '#description' => $this->t('The unique product batch number, as provided by the product manufacturer.'),
            '#required' => TRUE,
          ];
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

    // Operation time.
    $operation['time'] = $this->buildInlineWrapper();
    $operation['time']['#weight'] = -10;

    // Scheduled date and time.
    $operation['time']['timestamp'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start date and time'),
      '#description' => $this->t('The start date and time of the operation.'),
      '#default_value' => new DrupalDateTime(),
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#date_year_range' => '-15:+15',
    ];

    // Time taken.
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

    // Tractor time.
    $operation['tractor_time'] = $this->buildInlineWrapper();
    $operation['tractor_time']['#weight'] = -5;

    // Tractor hours start.
    $operation['tractor_time']['tractor_hours_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (start)'),
      '#description' => $this->t('The number of tractor hours displayed at the start of the job.'),
      '#required' => TRUE,
    ];

    // Tractor hours end.
    $operation['tractor_time']['tractor_hours_end'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (end)'),
      '#description' => $this->t('The number of tractor hours displayed at the end of the job.'),
      '#required' => TRUE,
      '#group' => 'operation',
    ];

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
    // @todo Load status options from log status options or workflow options.
    // @todo This may be different for each quick form.
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
    // @todo Do not recurse until issue 3259245 is fixed.
    $group_members = $this->groupMembership->getGroupMembers($groups, FALSE);

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
    $log = [
      'type' => $this->logType,
      'status' => $form_state->getValue('job_status'),
      'name' => $this->getLogName($form, $form_state),
      'timestamp' => $form_state->getValue('timestamp')->getTimestamp(),
      'asset' => $form_state->getValue('asset'),
      'flag' => $form_state->getValue('flag'),
      'owner' => $form_state->getValue('owner'),
    ];

    // Add equipment references.
    $tractor = $form_state->getValue('tractor');
    $machinery = array_filter($form_state->getValue('machinery'));
    $log['equipment'] = [...$machinery, $tractor];

    // Quantities.
    $quantity_keys = ['fuel_use'];
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

    // @todo Include remaining base form fields.
    // Tractor hours.

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
    if ($this->productsTab && $product_count = $form_state->getValue('product_count')) {
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

      // Ensure the quantity is an array and has a value.
      if (is_array($quantity) && !empty($quantity['value'])) {
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

}
