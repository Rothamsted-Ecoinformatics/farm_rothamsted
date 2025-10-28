<?php

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// phpcs:disable DrupalPractice.Objects.GlobalDrupal.GlobalDrupal

/**
 * Form for manually migrating plan files to dedicated fields.
 */
class PlanFileMigrationForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PlanFileMigrationForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'plan_file_migration_form';
  }

  /**
   * Access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The plan entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, PlanInterface $plan) {
    return $plan->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'administer rothamsted_experiment plan'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?PlanInterface $plan = NULL) {

    // Bail if no plan.
    if (empty($plan)) {
      return $form;
    }

    // Save the plan ID.
    $form['plan_id'] = [
      '#type' => 'hidden',
      '#value' => $plan->id(),
    ];

    $plan_link = \Drupal::service('link_generator')->generate($plan->label(), $plan->toUrl());
    $migration_list_link = \Drupal::service('link_generator')->generate($this->t('Back to migration list'), Url::fromRoute('farm_rothamsted_experiment.plan_file_migration_list'));
    $form['plan_info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>@back</p><h2>Migrate files for Plan #@id: @link</h2>', [
        '@back' => $migration_list_link,
        '@id' => $plan->id(),
        '@link' => $plan_link,
      ]),
    ];

    // Get all files attached to the plan.
    $files = $plan->get('file')->referencedEntities();

    // Sort files by creation time, newest first.
    usort($files, function ($a, $b) {
      return $b->getCreatedTime() - $a->getCreatedTime();
    });

    if (empty($files)) {
      $form['no_files'] = [
        '#type' => 'item',
        '#markup' => $this->t('This plan has no files attached.'),
      ];
      return $form;
    }

    // Build file table.
    $form['files'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Filename'),
        $this->t('Created'),
        $this->t('User'),
        $this->t('Download'),
        $this->t('Action'),
      ],
      '#empty' => $this->t('No files found.'),
    ];

    $user_storage = $this->entityTypeManager->getStorage('user');

    foreach ($files as $file) {
      $file_id = $file->id();

      // Get file metadata.
      $created = \Drupal::service('date.formatter')->format($file->getCreatedTime(), 'short');

      // Get the user who uploaded the file.
      $uid = $file->getOwnerId();
      $user = $user_storage->load($uid);
      $username = $user ? $user->getDisplayName() : $this->t('Unknown');

      // Build download link.
      $download_url = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()));
      $download_link = \Drupal::service('link_generator')->generate($this->t('Download'), $download_url);

      $form['files'][$file_id]['filename'] = [
        '#plain_text' => $file->getFilename(),
      ];

      $form['files'][$file_id]['created'] = [
        '#plain_text' => $created,
      ];

      $form['files'][$file_id]['user'] = [
        '#plain_text' => $username,
      ];

      $form['files'][$file_id]['download'] = [
        '#markup' => $download_link,
      ];

      $form['files'][$file_id]['action'] = [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- No action -'),
          'archive' => $this->t('Archive (remove from file field)'),
          'columns_file' => $this->t('Move to Columns file'),
          'column_levels_file' => $this->t('Move to Column Levels file'),
          'plot_attributes_file' => $this->t('Move to Plot Attributes file'),
          'plot_geometry_file' => $this->t('Move to Plot Geometry file'),
        ],
        '#default_value' => '',
      ];
    }

    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision message'),
      '#description' => $this->t('Describe the changes made during this migration.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate files'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('farm_rothamsted_experiment.plan_file_migration_list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $files = $form_state->getValue('files');

    if (empty($files)) {
      return;
    }

    // Track which destination fields have been assigned.
    $destination_fields = [
      'columns_file' => NULL,
      'column_levels_file' => NULL,
      'plot_attributes_file' => NULL,
      'plot_geometry_file' => NULL,
    ];

    foreach ($files as $file_id => $file_data) {
      $action = $file_data['action'];

      // Skip if no action or archive.
      if (empty($action) || $action === 'archive') {
        continue;
      }

      // Check if this destination field is already assigned.
      if ($destination_fields[$action] !== NULL) {
        $form_state->setErrorByName("files][$file_id][action", $this->t('Multiple files cannot be assigned to the same destination field. Another file is already assigned to @field.', [
          '@field' => $action,
        ]));
      }
      else {
        // Mark this destination field as assigned.
        $destination_fields[$action] = $file_id;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plan_id = $form_state->getValue('plan_id');
    $plan = $this->entityTypeManager->getStorage('plan')->load($plan_id);

    if (!$plan) {
      $this->messenger()->addError($this->t('Plan not found.'));
      return;
    }

    $files = $form_state->getValue('files');
    $revision_message = $form_state->getValue('revision_message');

    // Track files to keep in the file field.
    $files_to_keep = [];

    // Track files to move to dedicated fields.
    $destination_files = [
      'columns_file' => NULL,
      'column_levels_file' => NULL,
      'plot_attributes_file' => NULL,
      'plot_geometry_file' => NULL,
    ];

    // Get current file references.
    $current_files = [];
    foreach ($plan->get('file')->referencedEntities() as $file) {
      $current_files[$file->id()] = $file->id();
    }

    // Process each file action.
    foreach ($files as $file_id => $file_data) {
      $action = $file_data['action'];

      if (empty($action)) {
        // No action - keep in file field.
        $files_to_keep[] = $file_id;
      }
      elseif ($action === 'archive') {
        // Archive - don't add to files_to_keep.
        continue;
      }
      elseif (!isset($destination_files[$action])) {
        // Move to dedicated field.
        $destination_files[$action] = $file_id;
      }
    }

    // Update the plan.
    // Clear the file field and re-add files to keep.
    $plan->set('file', $files_to_keep);

    // Set the dedicated file fields.
    foreach ($destination_files as $field_name => $file_id) {
      if ($file_id !== NULL) {
        $plan->set($field_name, $file_id);
      }
    }

    // Save the plan with a new revision.
    $plan->setNewRevision(TRUE);
    $plan->setRevisionLogMessage($revision_message);
    $plan->setRevisionUserId($this->currentUser()->id());
    $plan->save();

    $plan_link = $plan->toLink($plan->label())->toString();
    $this->messenger()->addStatus($this->t('Files have been migrated successfully for @plan.', [
      '@plan' => $plan_link,
    ]));

    // Redirect to the migration list.
    $form_state->setRedirect('farm_rothamsted_experiment.plan_file_migration_list');
  }

}
