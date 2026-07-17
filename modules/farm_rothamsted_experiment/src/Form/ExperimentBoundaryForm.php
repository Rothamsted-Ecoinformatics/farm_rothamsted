<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\asset\Entity\Asset;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\plan\Entity\Plan;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Experiment boundary form.
 */
class ExperimentBoundaryForm extends ExperimentFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The geophp wrapper.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geophp;

  /**
   * Constructs a new ExperimentBoundaryForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp
   *   The GeoPHP wrapper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GeoPHPInterface $geophp) {
    $this->entityTypeManager = $entity_type_manager;
    $this->geophp = $geophp;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('geofield.geophp'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rothamsted_experiment_boundary_form';
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
    return $plan->access('update', $account, TRUE)->andIf(AccessResult::allowedIfHasPermission($account, 'create rothamsted_experiment plan boundary'));
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

    // Bail if the experiment already has a boundary.
    $boundary = $this->experimentBoundary($plan);
    if (!empty($boundary)) {
      $boundary_url = $boundary->toUrl()->setAbsolute()->toString();
      $this->messenger()->addWarning(
        new TranslatableMarkup(
          'The experiment %experiment already has a boundary: <a href="@boundary_url">%boundary</a>',
          [
            '%experiment' => $plan->label(),
            '@boundary_url' => $boundary_url,
            '%boundary' => $boundary->label(),
          ],
        ),
      );
      return $form;
    }

    // Ensure required fields are provided:
    $form['required_message'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'p',
      '#value' => new TranslatableMarkup('The experiment location is required to create an experiment boundary. Verify that this is correct before creating the experiment boundary.'),
    ];

    // Location for the experiment boundary parents.
    $default_locations = $plan->get('location')->referencedEntities();
    $form['location'] = [
      '#type' => 'entity_autocomplete',
      '#title' => new TranslatableMarkup('Experiment location'),
      '#description' => new TranslatableMarkup('The fields in which the experiment is located.'),
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
      '#default_value' => $default_locations,
      '#required' => TRUE,
    ];

    $form['geometry'] = [
      '#type' => 'managed_file',
      '#title' => new TranslatableMarkup('Experiment Boundary KML File'),
      '#description' => new TranslatableMarkup('If you have a KML file with GIS coordinates for the experiment boundary, please add it here.'),
      '#upload_location' => 'private://kml',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'kml',
        ],
      ],
    ];

    // Provide a checkbox to allow customizing the asset name.
    $form['name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'boundary-name'],
    ];
    $form['custom_name'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Customize experiment boundary name'),
      '#description' => new TranslatableMarkup('The name of the experiment boundary. Defaults to: "[Study Period] ([Study Plan name])"'),
      '#default_value' => FALSE,
      '#ajax' => [
        'callback' => [$this, 'boundaryNameCallback'],
        'wrapper' => 'boundary-name',
      ],
    ];
    if ($form_state->getValue('custom_name', FALSE)) {
      $form['name_wrapper']['name'] = [
        '#type' => 'textfield',
        '#title' => new TranslatableMarkup('Experiment boundary name'),
        '#maxlength' => 255,
        '#default_value' => $this->generateBoundaryName($form_state),
        '#required' => TRUE,
      ];
    }

    // Revision message.
    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => new TranslatableMarkup('Revision message'),
      '#description' => new TranslatableMarkup('Describe the reason for this change.'),
      '#default_value' => 'Create experiment boundary.',
      '#required' => TRUE,
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Create boundary'),
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback for boundary name field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The render array.
   */
  public function boundaryNameCallback(array $form, FormStateInterface $form_state) {
    return $form['name_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the plan.
    $plan = Plan::load($form_state->getValue('plan_id'));

    // If a custom boundary name was provided, use that. Otherwise generate one.
    $boundary_name = $this->generateBoundaryName($form_state);
    if ($form_state->getValue('custom_name', FALSE) && $form_state->hasValue('name')) {
      $boundary_name = $form_state->getValue('name');
    }

    // Set the experiment location.
    $location = $form_state->getValue('location');
    $plan->set('location', $location);

    // Create and save land asset.
    $boundary = Asset::create([
      'type' => 'land',
      'land_type' => 'experiment_boundary',
      'name' => $boundary_name,
      'parent' => $location,
      'is_fixed' => TRUE,
      'is_location' => TRUE,
    ]);

    // Add the geometry if provided.
    $file_ids = $form_state->getValue('geometry', []);
    if (count($file_ids)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));
      $path = $file->getFileUri();
      if (($data = file_get_contents($path)) && $geom = $this->geophp->load($data)) {
        $boundary->set('intrinsic_geometry', $geom->out('wkt'));
        $boundary->set('file', $file);
      }
    }

    // Save the boundary.
    $boundary->save();

    // Add land asset to the plan.
    $plan->get('asset')->appendItem($boundary);

    // Add message.
    $boundary_url = $boundary->toUrl()->setAbsolute()->toString();
    $this->messenger()->addStatus(
      new TranslatableMarkup(
        'Created experiment boundary: <a href="@boundary_url">%boundary</a>',
        [
          '@boundary_url' => $boundary_url,
          '%boundary' => $boundary->label(),
        ],
      ),
    );

    // Save the plan.
    $plan->setRevisionLogMessage($form_state->getValue('revision_message'));
    $plan->setNewRevision(TRUE);
    $plan->save();

    // Redirect to the plan page.
    $form_state->setRedirectUrl($plan->toUrl());
  }

  /**
   * Helper function to generate the boundary name.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string
   *   The boundary name.
   */
  protected function generateBoundaryName(FormStateInterface $form_state): string {
    if ($plan = Plan::load($form_state->getValue('plan_id'))) {
      $study_period = $plan->get('study_period_id')->value;
      return new TranslatableMarkup('@study_period (@plan_name): Experiment Boundary', ['@study_period' => $study_period, '@plan_name' => $plan->label()]);
    }
    return '';
  }

}
