<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\entity\EntityViewsData;


/**
 * Duplicates farmos core FarmEntityViewsData class.
 *
 * Re-enables logic that was limited to farmos core entity types in this commit: https://github.com/farmOS/farmOS/commit/e126cdfa2f52cae076cbfff7b0e2b67bfb1751c2
 *
 * @see FarmEntityViewsData
 */
class ResearchEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  protected function addReverseRelationships(array &$data, array $fields) {
    parent::addReverseRelationships($data, $fields);

    // Configure entity reference field filter plugins.
    foreach ($fields as $field) {

      // Only proceed if the target entity type is one we care about.
      $target_type = $field->getSettings()['target_type'];
      $target_types = [
        'rothamsted_program',
        'rothamsted_experiment',
        'roamsted_design',
        'rothamsted_proposal',
      ];
      if (!in_array($target_type, $target_types)) {
        continue;
      }

      // Get the field name.
      $field_name = $field->getName();

      // Iterate through the Views data tables and columns.
      foreach ($data as $table_name => $table_data) {
        foreach ($table_data as $table_field_name => $field_data) {

          // If this field doesn't have a filter handler, skip it.
          if (!isset($field_data['filter'])) {
            continue;
          }

          // Ensure that we are only altering the Views field we want.
          // This will either be the field name itself, or the field name plus
          // a `_target_id` suffix (depending on whether the field is a base or
          // bundle field, single or multiple values, etc).
          $table_field_names = [
            $field_name,
            $field_name . '_target_id',
          ];
          if (in_array($table_field_name, $table_field_names)) {

            // Use the taxonomy_index_tid plugin for taxonomy term reference
            // fields.
            // @see \Drupal\taxonomy\Hook\TaxonomyViewsHooks::fieldViewsDataAlter()
            if ($target_type == 'taxonomy_term') {
              $data[$table_name][$table_field_name]['filter']['id'] = 'taxonomy_index_tid';
            }

            // Use the entity_reference plugin for everything else.
            // @todo Refactor/remove this when the following core issues are resolved.
            // @see https://www.drupal.org/project/drupal/issues/3458099
            // @see https://www.drupal.org/project/drupal/issues/3438054
            else {
              $data[$table_name][$table_field_name]['filter']['id'] = 'entity_reference';
            }
          }
        }
      }
    }
  }


}
