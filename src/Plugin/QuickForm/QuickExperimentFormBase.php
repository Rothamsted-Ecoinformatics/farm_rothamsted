<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\farm_group\GroupMembershipInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_quick\Traits\QuickPrepopulateTrait;
use Drupal\user\UserInterface;
use Psr\Container\ContainerInterface;

/**
 * Base class for experiment plan quick forms.
 */
abstract class QuickExperimentFormBase extends QuickFormBase {

  use QuickPrepopulateTrait;
  use QuickLogTrait;

  /**
   * The valid file extensions.
   *
   * @var string[]
   */
  protected static array $validFileExtensions = ['pdf doc docx csv xls xlsx'];

  /**
   * The valid image file extensions.
   *
   * @var string[]
   */
  protected static array $validImageExtensions = ['png gif jpg jpeg'];

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
   * Helper function to generate inline quantity and unit elements from config.
   *
   * @param array $config
   *   Array of configuration options fully supporting drupal core options
   *
   *   Additional options:
   *     '#units_type' => 'select' or 'radios'
   *     '#units_options' => options array as per core.
   *
   * @param string $name
   *   String name used to submit element - units is appended for unit element
   *
   * @return array
   *   An array containing both form elements within wrapper to keep inline
   */
  protected function buildQuantityUnitsElement(array $config, string $name = 'abc') {

    // Flex container wrapper.
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'style' => ['display: flex; flex-wrap: wrap; column-gap: 0.5em; margin-bottom: -1em'],
      ],
    ];

    // Add description in the suffix to exclude from flex container.
    if (!empty($config['#description'])) {
      $element['#suffix'] = '<div class="form-item__description">' . $config['#description'] . '</div>';
      // We don't want description bleeding into main element.
      unset($config['#description']);
    }

    // Main quantity element.
    $element[$name] = $config;

    $unitsName = sprintf('%s_units', $name);

    // Units.
    if (isset($config['#units_options'])) {
      $element[$unitsName] = [];
      $element[$unitsName]['#type'] = (isset($config['#units_type'])) ? $config['#units_type'] : 'select';
      $element[$unitsName]['#title'] = $this->t('Units');
      $element[$unitsName]['#options'] = $config['#units_options'];
      $element[$unitsName]['#required'] = $config['#required'] ?? FALSE;
      $element[$unitsName]['#validated'] = TRUE;
    }

    return $element;
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
    $form['setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Setup'),
      '#group' => 'tabs',
      '#weight' => -10,
    ];

    // Operation tab.
    $form['operation'] = [
      '#type' => 'details',
      '#title' => $this->t('Operation'),
      '#group' => 'tabs',
      '#weight' => 10,
    ];

    // Load prepopulated assets.
    $default_assets = $this->getPrepopulatedEntities('asset');

    // Asset field.
    // @todo Decide on a widget for selecting assets.
    $form['setup']['asset'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Assets'),
      '#description' => $this->t('The asset that this log relates to. For experiments always specify the plot numbers when applying treatments.'),
      '#target_type' => 'asset',
      '#selection_settings' => [
        'target_bundles' => ['plot', 'plant'],
      ],
      '#tags' => TRUE,
      '#default_value' => $default_assets,
      '#required' => TRUE,
    ];

    // Build the tractor field if required.
    if ($this->tractorField) {
      $tractor_options = $this->getGroupMemberOptions(['Tractor Equipment'], ['equipment']);
      $form['setup']['tractor'] = [
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
      $form['setup']['machinery'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Machinery'),
        '#description' => $this->t('Select the equipment  used for this operation. You can expand the list by assigning Equipment Assets to the group ":group_names".', [':group_names' => $machinery_options_string]),
        '#options' => $equipment_options,
      ];
    }

    // Recommendation Number - text - optional.
    $form['setup']['recommendation_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recommendation Number'),
      '#description' => $this->t('A recommendation or reference number from the agronomist or crop consultant.'),
    ];

    // Recommendation files.
    $form['setup']['recommendation_files'] = [
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

    // Scheduled date and time.
    $form['operation']['timestamp'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Operation start date and time'),
      '#description' => $this->t('The start date and time of the operation.'),
      '#default_value' => new DrupalDateTime(),
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#date_year_range' => '-15:+15',
    ];

    // Tractor time.
    $form['operation']['tractor_time'] = $this->buildInlineWrapper();

    // Tractor hours start.
    $form['operation']['tractor_time']['tractor_hours_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (start)'),
      '#description' => $this->t('The number of tractor hours displayed at the start of the job.'),
      '#required' => TRUE,
    ];

    // Tractor hours end.
    $form['operation']['tractor_time']['tractor_hours_end'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (end)'),
      '#description' => $this->t('The number of tractor hours displayed at the end of the job.'),
      '#required' => TRUE,
      '#group' => 'operation',
    ];

    // Time taken.
    // @todo do we want a textfield with validation or a time widget.
    $form['operation']['time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time taken hh:mm'),
      '#description' => $this->t('The time taken to complete the job in hours and minutes.'),
      '#required' => TRUE,
    ];

    // Fuel use units options.
    $fuel_use_units_options = [
      '' => '- Select -',
      'l' => 'l',
      'gal' => 'gal',
    ];

    $form['operation']['fuel_use'] = $this->buildQuantityUnitsElement([
      '#title' => $this->t('Fuel use'),
      '#description' => $this->t('The amount of fuel used.'),
      '#type' => 'number',
      '#units_type' => 'select',
      '#units_options' => $fuel_use_units_options,
    ], 'fuel_use');

    // Photographs wrapper.
    $form['operation']['photographs'] = $this->buildInlineWrapper();

    // Crop Photographs.
    $form['operation']['photographs']['crop_photographs'] = [
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
    $form['operation']['photographs']['photographs_of_paper_records'] = [
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
    $form['operation']['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#description' => $this->t('Any additional notes.'),
    ];

    // Equipment settings.
    $form['operation']['equipment_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Equipment Settings'),
      '#description' => $this->t('An option to include any notes on the specific equipment settings used.'),
    ];

    // General operation fields.
    $form['operation']['general'] = $this->buildInlineWrapper();

    // Operator field.
    $operator_options = $this->getUserOptions(['farm_operator']);
    $form['operation']['general']['owner'] = [
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
    $form['operation']['general']['job_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Job status'),
      '#description' => $this->t('The current status of the job.'),
      '#options' => $status_options,
      '#required' => TRUE,
    ];

    // Flags.
    // @todo Build flag options for this bundle.
    $flag_options = [];
    $form['operation']['general']['flag'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag'),
      '#description' => $this->t('Flag this job if it is a priority, requires monitoring or review.'),
      '#options' => $flag_options,
    ];

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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // This method should be overridden by subclasses. The following only
    // exists to provide an example.
    // First build and array of log information.
    $log = $this->prepareLog($form, $form_state);

    // Subclasses should add additional data to the log at this point.
    $log['name'] = 'Quick form log name';

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
   * Helper function to build a sorted option list of taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   * @param int $parent
   *   The term ID under which to generate the tree. If 0, generate the tree
   *   for the entire vocabulary.
   * @param int|null $depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   *
   * @return array
   *   An array of term labels indexed by term ID and sorted alphabetically.
   */
  protected function getTermTreeOptions(string $vocabulary_name, int $parent = 0, int $depth = NULL): array {

    // Load terms.
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $terms = $term_storage->loadTree($vocabulary_name, $parent, $depth, TRUE);

    // Filter to active terms.
    $active_terms = array_filter($terms, function ($term) {
      return (int) $term->get('status')->value;
    });

    // Build options.
    $options = [];
    foreach ($active_terms as $term) {
      $options[$term->id()] = $term->label();
    }
    natsort($options);

    return $options;
  }

  /**
   * Helper function to build a sorted option list of child taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   * @param string $term_name
   *   The name of parent taxonomy term.
   * @param int|null $depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   *
   * @return array
   *   An array of taxonomy labels ordered alphabetically.
   */
  protected function getChildTermOptionsByName(string $vocabulary_name, string $term_name, int $depth = NULL): array {
    // Search for a parent term.
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $matching_terms = $term_storage->loadByProperties([
      'vid' => $vocabulary_name,
      'name' => $term_name,
      'status' => 1,
    ]);

    // If a parent term exists.
    if ($parent_term = reset($matching_terms)) {
      return $this->getTermTreeOptions($vocabulary_name, $parent_term->id(), $depth);
    }

    return [];
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
   * Helper function to get the managed_file upload location.
   *
   * @param string $entity_type
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param string $field_id
   *   The file field id.
   *
   * @return string
   *   The upload location uri.
   */
  protected function getFileUploadLocation(string $entity_type, string $bundle, string $field_id): string {

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');

    // Get field definitions.
    $field_definitions = $field_manager->getFieldDefinitions($entity_type, $bundle);

    // Bail if no field definition exists.
    // @todo Should we default to a standard location?
    if (empty($field_definitions[$field_id]) || !in_array($field_definitions[$field_id]->getType(), ['file', 'image'])) {
      return 'farm/quick';
    }

    // Get the field definition settings.
    $field_definition = $field_definitions[$field_id];
    $settings = $field_definition->getSettings();

    // The following is copied from FileItem::getUploadLocation().
    // We cannot use that method without instantiating a file entity.
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, []));
    return $settings['uri_scheme'] . '://' . $destination;
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
      'timestamp' => $form_state->getValue('timestamp')->getTimestamp(),
      'asset' => $form_state->getValue('asset'),
      'flag' => $form_state->getValue('flag'),
      'owner' => $form_state->getValue('owner'),
    ];

    // Add equipment references.
    $tractor = $form_state->getValue('tractor');
    $machinery = array_filter($form_state->getValue('machinery'));
    $log['equipment'] = [...$machinery, $tractor];

    // Files.
    $file_fields = ['recommendation_files'];
    $log['file'] = $this->getFileIds($file_fields, $form_state);

    // Images.
    $image_fields = ['crop_photographs', 'photographs_of_paper_records'];
    $log['image'] = $this->getImageIds($image_fields, $form_state);

    // Notes.
    // Define note fields to add.
    $note_fields = [];
    $note_fields[] = [
      'key' => 'recommendation_number',
      'label' => $this->t('Recommendation Number'),
    ];
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
    // Time taken.
    // Fuel use.

    return $log;
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
        $notes[] = $field_info['label'] . ': ' . $form_state->getValue($key);
      }
    }

    // Split notes onto separate lines.
    return [
      'value' => implode(PHP_EOL, $notes),
      'format' => 'default',
    ];
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
