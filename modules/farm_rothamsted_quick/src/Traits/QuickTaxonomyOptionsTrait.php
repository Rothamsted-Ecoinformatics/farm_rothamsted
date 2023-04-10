<?php

namespace Drupal\farm_rothamsted_quick\Traits;

use Drupal\Core\Url;

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
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $matching_terms = $term_storage->loadByProperties([
      'vid' => $vocabulary_name,
      'name' => $term_name,
      'status' => 1,
    ]);

    // If a parent term exists.
    $options = [];
    if ($parent_term = reset($matching_terms)) {
      $options = $this->getTermTreeOptions($vocabulary_name, $parent_term->id(), $depth, FALSE);
    }

    // Add a warning if empty.
    if (empty($options)) {
      $this->addEmptyTaxonomyWarning($vocabulary_name, $term_name);
    }

    return $options;
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
   * @param bool $warning
   *   Boolean to raise a warning if there are no options.
   *
   * @return array
   *   An array of term labels indexed by term ID and sorted alphabetically.
   */
  protected function getTermTreeOptions(string $vocabulary_name, int $parent = 0, int $depth = NULL, bool $warning = TRUE): array {

    // Load terms.
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($vocabulary_name, $parent, $depth, TRUE);

    // Filter to active terms.
    $active_terms = array_filter($terms, function ($term) {
      return (int) $term->get('status')->value;
    });

    // Build options.
    $options = [];
    foreach ($active_terms as $term) {
      // This approach taken from core TaxonomyIndexTid views filter plugin.
      $label = str_repeat('-', $term->depth) . $term->label();
      $options[$term->id()] = $label;
    }

    // Add warning if no options.
    if ($warning && empty($options)) {
      $this->addEmptyTaxonomyWarning($vocabulary_name);
    }

    return $options;
  }

  /**
   * Helper function to log warning messages if taxonomies are not configured.
   *
   * @param string $vocabulary_name
   *   The vocabulary name.
   * @param string|null $child_name
   *   The child term name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function addEmptyTaxonomyWarning(string $vocabulary_name, string $child_name = NULL) {
    $vocab = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vocabulary_name);
    $url = new Url('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => $vocabulary_name]);
    $url = $url->toString();
    $missing_text = empty($child_name) ? 'No @label terms found.' : 'No child terms found for %child.';
    $configure_text = $this->t("$missing_text Add a @label term <a href=\"@url\">here</a>.", ['@label' => $vocab->label(), '@url' => $url, '%child' => $child_name]);
    $this->messenger()->addWarning($configure_text);
  }

}
