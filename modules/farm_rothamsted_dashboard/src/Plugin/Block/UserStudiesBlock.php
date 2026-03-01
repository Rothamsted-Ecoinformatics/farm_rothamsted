<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user studies block.
 *
 * @Block(
 *   id = "rothamsted_user_studies",
 *   admin_label = @Translation("User Studies")
 * )
 */
class UserStudiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a UserStudiesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $plan_status = [
      'requested',
      'planning',
      'active',
    ];
    $uid = $this->currentUser->id();
    $proposal_query = $this->entityTypeManager->getStorage('plan')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'rothamsted_experiment')
      ->condition('status', $plan_status, 'IN')
      ->sort('name', 'ASC');
    $or = $proposal_query->orConditionGroup();
    $or
      ->condition('experiment_design.entity.statistician.entity.farm_user.entity.uid', $uid)
      ->condition('experiment_design.entity.experiment.entity.researcher.entity.farm_user.entity.uid', $uid);
    $proposal_query->condition($or);
    $ids = $proposal_query->execute();
    $caption = new PluralTranslatableMarkup(
      count($ids),
      '@count active study',
      '@count active studies',
    );
    $table = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Status'),
        ],
        [
          'data' => $this->t('Study Period ID'),
        ],
        [
          'data' => $this->t('Study'),
        ],

        [
          'data' => $this->t('Design'),
        ],
        [
          'data' => $this->t('Experiment'),
        ],
        [
          'data' => $this->t('Location'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\plan\Entity\PlanInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('plan')->loadMultiple($ids);
    foreach ($entities as $entity) {

      // Build links to associated design and experiment.
      $design_link = NULL;
      $experiment_link = NULL;
      if (!$entity->get('experiment_design')->isEmpty()) {
        /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface[] $designs */
        $designs = $entity->get('experiment_design')->referencedEntities();
        $design = reset($designs);
        $design_link = $design->toLink($design->label());
        if (!$design->get('experiment')->isEmpty()) {
          /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface[] $experiments */
          $experiments = $design->get('experiment')->referencedEntities();
          $experiment = reset($experiments);
          $experiment_link = $experiment->toLink($experiment->label());
        }
      }
      $table['#rows'][$entity->id()] = [
        [
          'data' => $entity->get('status')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('study_period_id')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $design_link,
        ],
        [
          'data' => $experiment_link,
        ],
        [
          'data' => $entity->get('location')->view(['label' => 'visually_hidden']),
        ],
      ];
    }

    // Render the table in a wrapper container to constrain the height.
    $render['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['rothamsted-user-entity-table'],
      ],
      '#attached' => [
        'library' => ['farm_rothamsted_dashboard/user-entity'],
      ],
      'table' => $table,
    ];

    return $render;
  }

}
