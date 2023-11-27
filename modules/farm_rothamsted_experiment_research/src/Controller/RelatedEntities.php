<?php

namespace Drupal\farm_rothamsted_experiment_research\Controller;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface;
use Drupal\plan\Entity\PlanInterface;

/**
 * Controller to generate list of related research entities.
 */
class RelatedEntities extends ControllerBase {

  /**
   * Title callback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function title() {
    return $this->t('Related');
  }

  /**
   * Access callback for asset pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\asset\Entity\AssetInterface|null $asset
   *   The asset entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function assetAccess(AccountInterface $account, AssetInterface $asset = NULL): AccessResultInterface {

    // Ensure access to view the asset.
    $access = $asset->access('view', $account, TRUE);

    // If view access check for existing plan relationship.
    if ($access->isAllowed()) {
      $plan_query = $this->entityTypeManager()->getStorage('plan')->getAggregateQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'rothamsted_experiment');
      $or_group = $plan_query->orConditionGroup()
        ->condition('asset', $asset->id())
        ->condition('location', $asset->id())
        ->condition('plot', $asset->id());
      $plan_query->condition($or_group);
      $has_relationship = AccessResultForbidden::forbiddenIf($plan_query->count()->execute() == 0);
      $access = $access->orif($has_relationship);
    }
    return $access;
  }

  /**
   * Asset relationshps.
   *
   * @param \Drupal\asset\Entity\AssetInterface $asset
   *   The asset entity.
   *
   * @return array
   *   Render array.
   */
  public function assetRelationships(AssetInterface $asset): array {

    $plan_query = $this->entityTypeManager()->getStorage('plan')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'rothamsted_experiment');

    $or_group = $plan_query->orConditionGroup()
      ->condition('asset', $asset->id())
      ->condition('location', $asset->id())
      ->condition('plot', $asset->id());

    $plan_query->condition($or_group);

    $plans = [];
    $plan_ids = $plan_query->execute();
    if (!empty($plan_ids)) {
      $plans = $this->entityTypeManager()->getStorage('plan')->loadMultiple($plan_ids);
    }

    return $this->buildIndex([], [], [], [], $plans);
  }

  /**
   * Proposal relationships.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface $rothamsted_proposal
   *   The proposal entity.
   *
   * @return array
   *   Render array.
   */
  public function proposalRelationships(RothamstedProposalInterface $rothamsted_proposal): array {

    // Proposal relationships are simple. The proposal has fields to reference
    // all related entities.
    $programs = $rothamsted_proposal->get('program')->referencedEntities();
    $experiments = $rothamsted_proposal->get('experiment')->referencedEntities();
    $designs = $rothamsted_proposal->get('design')->referencedEntities();
    $plans = $rothamsted_proposal->get('plan')->referencedEntities();
    return $this->buildIndex([], $programs, $experiments, $designs, $plans);
  }

  /**
   * Program relationshps.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface $rothamsted_program
   *   The program entity.
   *
   * @return array
   *   Render array.
   */
  public function programRelationships(RothamstedProgramInterface $rothamsted_program): array {

    // Proposals reference the program.
    $proposals = $this->entityTypeManager()->getStorage('rothamsted_proposal')->loadByProperties([
      'program' => $rothamsted_program->id(),
    ]);

    // Experiments reference the program.
    $experiments = $this->entityTypeManager()->getStorage('rothamsted_experiment')->loadByProperties([
      'program' => $rothamsted_program->id(),
    ]);

    // Designs reference the experiments.
    $designs = [];
    if (!empty($experiments)) {
      $designs = $this->entityTypeManager()->getStorage('rothamsted_design')->loadByProperties([
        'experiment' => array_keys($experiments),
      ]);
    }

    // Plans reference the design.
    $plans = [];
    if (!empty($designs)) {
      $plans = $this->entityTypeManager()->getStorage('plan')->loadByProperties([
        'type' => 'rothamsted_experiment',
        'experiment_design' => array_keys($designs),
      ]);
    }

    return $this->buildIndex($proposals, [], $experiments, $designs, $plans);
  }

  /**
   * Experiment relationships.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface $rothamsted_experiment
   *   The experiment entity.
   *
   * @return array
   *   Render array.
   */
  public function experimentRelationships(RothamstedExperimentInterface $rothamsted_experiment): array {

    // Proposals reference the experiment.
    $proposals = $this->entityTypeManager()->getStorage('rothamsted_proposal')->loadByProperties([
      'experiment' => $rothamsted_experiment->id(),
    ]);

    // Experiment references the programs.
    $programs = $rothamsted_experiment->get('program')->referencedEntities();

    // Design references the experiment.
    $designs = $this->entityTypeManager()->getStorage('rothamsted_design')->loadByProperties([
      'experiment' => $rothamsted_experiment->id(),
    ]);

    // Plans reference the design.
    $plans = [];
    if (!empty($designs)) {
      $plans = $this->entityTypeManager()->getStorage('plan')->loadByProperties([
        'type' => 'rothamsted_experiment',
        'experiment_design' => array_keys($designs),
      ]);
    }

    return $this->buildIndex($proposals, $programs, [], $designs, $plans);
  }

  /**
   * Design relationships.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface $rothamsted_design
   *   The design entity.
   *
   * @return array
   *   Render array.
   */
  public function designRelationships(RothamstedDesignInterface $rothamsted_design): array {

    // Proposals reference the design.
    $proposals = $this->entityTypeManager()->getStorage('rothamsted_proposal')->loadByProperties([
      'design' => $rothamsted_design->id(),
    ]);

    // Design references the experiment.
    // Experiment references the programs.
    $programs = [];
    if ($experiment = $rothamsted_design->get('experiment')->entity) {
      $programs = $experiment->get('program')->referencedEntities();
    }

    // Plans reference the design.
    $plans = $this->entityTypeManager()->getStorage('plan')->loadByProperties([
      'type' => 'rothamsted_experiment',
      'experiment_design' => $rothamsted_design->id(),
    ]);

    return $this->buildIndex($proposals, $programs, [$experiment], [], $plans);
  }

  /**
   * Plan relationships.
   *
   * @param \Drupal\plan\Entity\PlanInterface $plan
   *   The plan entity.
   *
   * @return array
   *   Render array.
   */
  public function planRelationships(PlanInterface $plan): array {

    // Proposals reference the plan.
    $proposals = $this->entityTypeManager()->getStorage('rothamsted_proposal')->loadByProperties([
      'plan' => $plan->id(),
    ]);

    // Plan references the design.
    // Design references the experiment.
    // Experiment references the programs.
    $experiment = NULL;
    $programs = [];
    if ($design = $plan->get('experiment_design')->entity) {
      if ($experiment = $design->get('experiment')->entity) {
        $programs = $experiment->get('program')->referencedEntities();
      }
    }

    return $this->buildIndex($proposals, $programs, [$experiment], [$design], []);
  }

  /**
   * Helper function to build indices for all entity types.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface[] $proposals
   *   Proposal entities.
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface[] $programs
   *   Program entities.
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface[] $experiments
   *   Experiment entities.
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface[] $designs
   *   Design entities.
   * @param \Drupal\plan\Entity\PlanInterface[] $plans
   *   Plan entities.
   *
   * @return array
   *   Render array with indices for all entity types.
   */
  protected function buildIndex(array $proposals, array $programs, array $experiments, array $designs, array $plans): array {

    // Build menu items using admin_block.
    $menu_items = [];

    // Add programs.
    if (!empty($programs)) {
      $program_links = array_map(function (EntityInterface $entity) {
        return [
          'title' => $entity->label(),
          'url' => $entity->toUrl(),
        ];
      }, $programs);
      $menu_items['programs'] = [
        '#theme' => 'admin_block',
        '#weight' => 0,
        '#block' => [
          'title' => $this->t('Programs'),
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $program_links,
          ],
        ],
      ];
    }

    // Add proposals.
    if (!empty($proposals)) {
      $proposal_links = array_map(function (EntityInterface $entity) {
        return [
          'title' => $entity->label(),
          'url' => $entity->toUrl(),
        ];
      }, $proposals);
      $menu_items['proposals'] = [
        '#theme' => 'admin_block',
        '#weight' => 10,
        '#block' => [
          'title' => $this->t('Proposals'),
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $proposal_links,
          ],
        ],
      ];
    }

    // Add experiments.
    if (!empty($experiments)) {
      $experiment_links = array_map(function (EntityInterface $entity) {
        return [
          'title' => $entity->label(),
          'url' => $entity->toUrl(),
        ];
      }, $experiments);
      $menu_items['experiments'] = [
        '#theme' => 'admin_block',
        '#weight' => 20,
        '#block' => [
          'title' => $this->t('Experiments'),
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $experiment_links,
          ],
        ],
      ];
    }

    // Add designs.
    if (!empty($designs)) {
      $design_links = array_map(function (EntityInterface $entity) {
        return [
          'title' => $entity->label(),
          'url' => $entity->toUrl(),
        ];
      }, $designs);
      $menu_items['designs'] = [
        '#theme' => 'admin_block',
        '#weight' => 40,
        '#block' => [
          'title' => $this->t('Designs'),
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $design_links,
          ],
        ],
      ];
    }

    // Add plans.
    if (!empty($plans)) {
      $plan_links = array_map(function (EntityInterface $entity) {
        return [
          'title' => $entity->label(),
          'url' => $entity->toUrl(),
        ];
      }, $plans);
      $menu_items['plans'] = [
        '#theme' => 'admin_block',
        '#weight' => 60,
        '#block' => [
          'title' => $this->t('Plans'),
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $plan_links,
          ],
        ],
      ];
    }
    return $menu_items;
  }

}
