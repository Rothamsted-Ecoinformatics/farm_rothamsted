<?php

namespace Drupal\farm_rothamsted_researcher\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "rothamsted_orcid_link",
 *   label = @Translation("Orcid Link"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class OrcidLinkWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $main_widget['value']['#field_prefix'] = 'https://orcid.org/';
    $main_widget['value']['#pattern'] = "\b\d{4}-\d{4}-\d{4}-\d{4}\b";
    return $main_widget;
  }

}
