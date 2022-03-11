<?php

namespace Drupal\farm_rothamsted\Traits;

/**
 * Helper functions for loading taxonomy term options.
 */
trait QuickTaxonomyOptionsTrait {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Helper function to build a sorted option list of child taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   * @param string $term_name
   *   The name of parent taxonomy term.
   * @param int|null $depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   *
   * @return array
   *   An array of taxonomy labels ordered alphabetically.
   */
  protected function getChildTermOptionsByName(string $vocabulary_name, string $term_name, int $depth = NULL): array {
    // Search for a parent term.
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $matching_terms = $term_storage->loadByProperties([
      'vid' => $vocabulary_name,
      'name' => $term_name,
      'status' => 1,
    ]);

    // If a parent term exists.
    if ($parent_term = reset($matching_terms)) {
      return $this->getTermTreeOptions($vocabulary_name, $parent_term->id(), $depth);
    }

    return [];
  }

  /**
   * Helper function to build a sorted option list of taxonomy terms.
   *
   * @param string $vocabulary_name
   *   The name of vocabulary.
   * @param int $parent
   *   The term ID under which to generate the tree. If 0, generate the tree
   *   for the entire vocabulary.
   * @param int|null $depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   *
   * @return array
   *   An array of term labels indexed by term ID and sorted alphabetically.
   */
  protected function getTermTreeOptions(string $vocabulary_name, int $parent = 0, int $depth = NULL): array {

    // Load terms.
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getSTorage('taxonomy_term');
    $terms = $term_storage->loadTree($vocabulary_name, $parent, $depth, TRUE);

    // Filter to active terms.
    $active_terms = array_filter($terms, function ($term) {
      return (int) $term->get('status')->value;
    });

    // Build options.
    $options = [];
    foreach ($active_terms as $term) {
      $options[$term->id()] = $term->label();
    }
    natsort($options);

    return $options;
  }

}
