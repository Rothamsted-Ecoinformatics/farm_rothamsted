<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_quick\Plugin\Action\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_quick\QuickFormPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action deriver that finds rothamsted quick forms.
 *
 * @see \Drupal\farm_rothamsted_quick\Plugin\Action\QuickExperimentAction
 */
class QuickExperimentActionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The quick form manager.
   *
   * @var \Drupal\farm_quick\QuickFormPluginManager
   */
  protected $quickFormManager;

  /**
   * Constructs a new QuickExperimentActionDeriver object.
   *
   * @param \Drupal\farm_quick\QuickFormPluginManager $quick_form_manager
   *   The quick form manager service.
   */
  public function __construct(QuickFormPluginManager $quick_form_manager) {
    $this->quickFormManager = $quick_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.quick_form'),
    );
  }

  /**
   * Array of experiment quick form IDs to create actions for.
   *
   * @var string[]
   */
  protected $experimentQuickFormIds = [
    'drilling',
    'fertiliser',
    'combine_harvest',
    'trailer_harvest',
    'field_operations',
    'spraying',
  ];

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {

      $definitions = [];
      foreach ($this->getApplicableQuickForms() as $quick_form_id => $quick_form_definition) {
        $definition = $base_plugin_definition;
        $definition['type'] = 'asset';
        $definition['label'] = new TranslatableMarkup('@quick_form_label Quick Form Action', ['@quick_form_label' => $quick_form_definition->getLabel()]);
        $definition['confirm_form_route_name'] = "farm.quick.$quick_form_id";
        $definitions[$quick_form_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return $this->derivatives;
  }

  /**
   * Helper function to return applicable quick form definitions.
   *
   * @return \Drupal\farm_quick\Plugin\QuickForm\QuickFormInterface[]
   *   An array of applicable quick forms.
   */
  protected function getApplicableQuickForms(): array {

    // Filter to only applicable quick form definitions.
    $quick_forms = $this->quickFormManager->getDefinitions();
    $definitions = array_filter($quick_forms, function ($quick_form_id) {
      return in_array($quick_form_id, $this->experimentQuickFormIds);
    }, ARRAY_FILTER_USE_KEY);

    // Return instances of each quick form.
    return array_map(function ($quick_form_definition) {
      return $this->quickFormManager->createInstance($quick_form_definition['id']);
    }, $definitions);
  }

}
