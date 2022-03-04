<?php

namespace Drupal\farm_rothamsted\Plugin\Action\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\farm_quick\QuickFormManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action deriver that finds rothamsted quick forms.
 *
 * @see \Drupal\farm_rothamsted\Plugin\Action\QuickExperimentAction
 */
class QuickExperimentActionDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The quick form manager.
   *
   * @var \Drupal\farm_quick\QuickFormManager
   */
  protected $quickFormManager;

  /**
   * Constructs a new QuickExperimentActionDeriver object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\farm_quick\QuickFormManager $quick_form_manager
   *   The quick form manager service.
   */
  public function __construct(TranslationInterface $string_translation, QuickFormManager $quick_form_manager) {
    $this->stringTranslation = $string_translation;
    $this->quickFormManager = $quick_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('string_translation'),
      $container->get('plugin.manager.quick_form'),
    );
  }

  /**
   * Array of experiment quick form IDs to create actions for.
   *
   * @var string[]
   */
  protected $experimentQuickFormIds = [
    'farm_rothamsted_drilling_quick_form',
    'farm_rothamsted_fertiliser_quick_form',
    'farm_rothamsted_harvest_quick_form',
    'field_operations',
    'farm_rothamsted_spraying_quick_form',
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
        $definition['label'] = $this->t('@quick_form_label Quick Form Action', ['@quick_form_label' => $quick_form_definition->getLabel()]);
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
