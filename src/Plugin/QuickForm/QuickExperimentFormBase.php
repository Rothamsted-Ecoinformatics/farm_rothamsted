<?php

namespace Drupal\farm_rothamsted\Plugin\QuickForm;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\farm_group\GroupMembershipInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickPrepopulateTrait;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Psr\Container\ContainerInterface;

/**
 * Base class for experiment plan quick forms.
 */
abstract class QuickExperimentFormBase extends QuickFormBase {

  use QuickPrepopulateTrait;

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
    $plots = $this->getPrepopulatedEntities('asset');
    $default_plots = implode(', ', array_map(function (AssetInterface $asset) {
      return $asset->label();
    }, $plots));

    // Asset field.
    $form['setup']['asset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assets'),
      '#description' => $this->t('The asset that this log relates to. For experiments always specify the plot numbers when applying treatments.'),
      // @todo Decide on a widget for selecting assets.
      '#default_value' => $default_plots ?: 'TBD',
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

    // Recommendation files - file picker - optional.
    // @todo Determine the final file upload location.
    $form['setup']['recommendation_files'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Recommendation files'),
      '#description' => $this->t('A PDF, word or excel file with the agronomist or crop consultant recommendations.'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx csv xls xlsx'],
      ],
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

    // Tractor hours start.
    $form['operation']['tractor_hours_start'] = [
      '#type' => 'number',
      '#title' => $this->t('Tractor hours (start)'),
      '#description' => $this->t('The number of tractor hours displayed at the start of the job.'),
      '#required' => TRUE,
    ];

    // Tractor hours end.
    $form['operation']['tractor_hours_end'] = [
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

    // Fuel use.
    $form['operation']['fuel_use'] = [
      '#type' => 'number',
      '#title' => $this->t('Fuel use'),
      '#description' => $this->t('The amount of fuel used.'),
    ];

    // Crop Photographs.
    // @todo Determine the final file upload location.
    $form['operation']['crop_photographs'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crop Photograph(s)'),
      '#description' => $this->t('A photograph of the crop, if applicable.'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg'],
      ],
    ];

    // Photographs of paper records.
    // @todo Determine the final file upload location.
    $form['operation']['photographs_of_paper_records'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Photographs of paper record(s)'),
      '#description' => $this->t('One or more photographs of any paper records, if applicable.'),
      '#upload_location' => 'private://quick',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf png gif jpg jpeg'],
      ],
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

    // Operator field.
    $operator_options = $this->getUserOptions(['farm_operator']);
    $form['operation']['users'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#description' => $this->t('The operator(s) who carried out the task.'),
      '#options' => $operator_options,
      '#required' => TRUE,
    ];

    // Build status options.
    // @todo Load status options from log status options or workflow options.
    // @todo This may be different for each quick form.
    $status_options = [];

    // Job status - checkboxes - required.
    $form['operation']['job_status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Job status'),
      '#description' => $this->t('The current status of the job.'),
      '#options' => $status_options,
      '#required' => TRUE,
    ];

    // Flags.
    // @todo Build flag options for this bundle.
    $flag_options = [];
    $form['operation']['flag'] = [
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
    $group_members = $this->groupMembership->getGroupMembers($groups, TRUE);

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
   *
   * @return array
   *   An array of term labels indexed by term ID and sorted alphabetically.
   */
  protected function getTermOptions(string $vocabulary_name): array {

    // Load active terms.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vocabulary_name,
      'status' => 1,
    ]);

    // Build options.
    $options = array_map(function (TermInterface $term) {
      return $term->label();
    }, $terms);
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
   *
   * @return array
   *   An array of taxonomy labels ordered alphabetically.
   */
  protected function getChildTermOptions(string $vocabulary_name, string $term_name): array {

    // Build array of options.
    $options = [];

    // Search for a parent term.
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $matching_terms = $term_storage->loadByProperties([
      'vid' => $vocabulary_name,
      'name' => $term_name,
      'status' => 1,
    ]);

    // If a parent term exists.
    if ($parent_term = reset($matching_terms)) {

      // Build option for each active child term.
      foreach ($term_storage->loadChildren($parent_term->id()) as $term) {
        if ($term->get('status')->value) {
          $options[$term->id()] = $term->label();
        }
      }
    }

    // Sort options.
    natsort($options);

    return $options;
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

}
