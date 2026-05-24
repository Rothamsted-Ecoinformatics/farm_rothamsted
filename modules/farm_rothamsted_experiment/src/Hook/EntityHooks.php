<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Entity hook implementations for farm_rothamsted_experiment.
 */
class EntityHooks {

  /**
   * Constructs an EntityHooks object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('plan_view')]
  public function planView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, string $view_mode): void {

    // Only modify experiment plans.
    if ($entity->bundle() !== 'rothamsted_experiment') {
      return;
    }

    $boundary = NULL;
    /** @var \Drupal\asset\Entity\AssetInterface[] $plan_assets */
    $plan_assets = $entity->get('asset')->referencedEntities();
    foreach ($plan_assets as $plan_asset) {
      if ($plan_asset->bundle() == 'land' && $plan_asset->get('land_type')->value == 'experiment_boundary') {
        $boundary = $plan_asset;
        break;
      }
    }

    // Ensure experiment boundary exists.
    if (empty($boundary)) {
      $url = Url::fromRoute('farm_rothamsted_experiment.experiment_boundary_form', ['plan' => $entity->id()])->setAbsolute()->toString();
      $this->messenger->addWarning(new TranslatableMarkup('No experiment boundary has been created. <a href=":link">Create experiment boundary</a>', [':link' => $url]));
    }

    $has_plots = !$entity->get('plot')->isEmpty();
    if (!$has_plots) {
      $url = Url::fromRoute('farm_rothamsted_experiment.experiment_plot_form', ['plan' => $entity->id()])->setAbsolute()->toString();
      $this->messenger->addWarning(new TranslatableMarkup('No experiment plots have been created. <a href=":link">Create experiment plots</a>', [':link' => $url]));
    }

    // Create details for each field group.
    $field_groups = [
      'default' => [
        'location' => 'main',
        'title' => 'Experiment',
        'weight' => -50,
      ],
      'meta' => [
        'location' => 'sidebar',
        'title' => new TranslatableMarkup('Status'),
        'weight' => 0,
      ],
      'file' => [
        'location' => 'main',
        'title' => new TranslatableMarkup('Files'),
        'weight' => 150,
      ],
    ] + $this->moduleHandler->invokeAll('farm_ui_theme_field_groups', ['plan', 'rothamsted_experiment']);
    foreach ($field_groups as $tab_id => $tab_info) {
      $tab_id = "{$tab_id}_field_group";
      $build[$tab_id] = [
        '#type' => 'details',
        '#title' => $tab_info['title'],
        '#weight' => $tab_info['weight'],
        '#open' => $tab_id === 'default_field_group',
      ];
    }

    // Ask modules for a list of field group items.
    $field_map = $this->moduleHandler->invokeAll(
      'farm_ui_theme_field_group_items',
      ['plan', 'rothamsted_experiment'],
    );

    // Set field group for each display component.
    foreach ($display->getComponents() as $field_id => $options) {

      // Don't modify the comment field.
      if ($field_id == 'comment') {
        continue;
      }

      $group = $field_map[$field_id] ?? 'default';
      if (isset($build[$field_id])) {
        $build["{$group}_field_group"][$field_id] = $build[$field_id];
        unset($build[$field_id]);
      }
    }
  }

}
