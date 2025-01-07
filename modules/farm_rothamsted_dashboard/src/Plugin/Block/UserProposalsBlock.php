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
 * Provides a user research block.
 *
 * @Block(
 *   id = "rothamsted_user_proposals",
 *   admin_label = @Translation("User Proposals")
 * )
 */
class UserProposalsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a UserProposalsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
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
    $proposal_status = [
      'draft',
      'submitted',
      'approved',
      'rejected',
      'planning',
    ];
    $uid = $this->currentUser->id();
    $proposal_query = $this->entityTypeManager->getStorage('rothamsted_proposal')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', $proposal_status, 'IN')
      ->sort('name', 'ASC');
    $or = $proposal_query->orConditionGroup();
    $or
      ->condition('contact.entity.farm_user.entity.uid', $uid)
      ->condition('statistician.entity.farm_user.entity.uid', $uid)
      ->condition('data_steward.entity.farm_user.entity.uid', $uid);
    $proposal_query->condition($or);
    $ids = $proposal_query->execute();

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '@count active proposal',
      '@count active proposals',
    );
    $table = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Status'),
        ],
        [
          'data' => $this->t('Name'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('rothamsted_proposal')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $table['#rows'][$entity->id()] = [
        [
          'data' => $entity->get('status')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->toLink($entity->label()),
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
