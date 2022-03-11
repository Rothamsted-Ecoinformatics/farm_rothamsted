<?php

namespace Drupal\farm_rothamsted\Traits;

use Drupal\Component\Render\PlainTextOutput;

/**
 * Helper functions for including managed_file questions in quick forms.
 */
trait QuickFileTrait {

  /**
   * The valid file extensions.
   *
   * @var string[]
   */
  protected static array $validFileExtensions = ['pdf doc docx csv xls xlsx'];

  /**
   * The valid image file extensions.
   *
   * @var string[]
   */
  protected static array $validImageExtensions = ['png gif jpg jpeg'];

  /**
   * Helper function to get the managed_file upload location.
   *
   * @param string $entity_type
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param string $field_id
   *   The file field id.
   *
   * @return string
   *   The upload location uri.
   */
  protected function getFileUploadLocation(string $entity_type, string $bundle, string $field_id): string {

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');

    // Get field definitions.
    $field_definitions = $field_manager->getFieldDefinitions($entity_type, $bundle);

    // Bail if no field definition exists.
    // @todo Should we default to a standard location?
    if (empty($field_definitions[$field_id]) || !in_array($field_definitions[$field_id]->getType(), ['file', 'image'])) {
      return 'farm/quick';
    }

    // Get the field definition settings.
    $field_definition = $field_definitions[$field_id];
    $settings = $field_definition->getSettings();

    // The following is copied from FileItem::getUploadLocation().
    // We cannot use that method without instantiating a file entity.
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, []));
    return $settings['uri_scheme'] . '://' . $destination;
  }

}
