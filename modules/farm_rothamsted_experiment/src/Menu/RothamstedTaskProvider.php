<?php

namespace Drupal\farm_rothamsted_experiment\Menu;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entity\Menu\EntityLocalTaskProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a set of tasks to view, edit and duplicate an entity.
 */
class RothamstedTaskProvider implements EntityLocalTaskProviderInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * Constructs a DefaultEntityLocalTaskProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityTypeInterface $entity_type, TranslationInterface $string_translation) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('string_translation'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildLocalTasks(EntityTypeInterface $entity_type) {

    // Note: delete-form was intentionally omitted, to match core. See #1834002.
    $link_templates = [];
    foreach (['canonical', 'edit-form', 'duplicate-form', 'version-history'] as $rel) {
      if ($entity_type->hasLinkTemplate($rel)) {
        $link_templates[] = str_replace('-', '_', $rel);
      }
    }

    $tasks = [];
    if (count($link_templates) > 1) {
      $entity_type_id = $entity_type->id();
      $base = reset($link_templates);
      $parent = "entity.entity_tasks:entity.$entity_type_id.canonical";

      $titles = [
        'canonical' => $entity_type->getLabel(),
        'edit_form' => $this->t('Edit'),
        'duplicate_form' => $this->t('Duplicate'),
        'version_history' => $this->t('Revisions'),
      ];

      $weight = 0;
      foreach ($link_templates as $rel) {
        $route_name = "entity.$entity_type_id.$rel";
        $tasks[$route_name] = [
          'title' => $titles[$rel],
          'route_name' => $route_name,
          'base_route' => "entity.$entity_type_id.$base",
          'weight' => $weight,
        ];

        if ($rel == 'canonical') {
          $tasks["$entity_type_id.canonical.sub"] = [
            'title' => $titles[$rel],
            'route_name' => $route_name,
            'parent_id' => $parent,
            'weight' => $weight,
          ];
        }

        if ($rel != 'canonical') {
          unset($tasks[$route_name]['base_route']);
          $tasks[$route_name]['parent_id'] = $parent;
        }

        $weight += 10;
      }
    }
    return $tasks;
  }

}
