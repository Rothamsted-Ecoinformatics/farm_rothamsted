<?php

namespace Drupal\farm_rothamsted_dashboard\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Dashboard search form.
 */
class RothamstedSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_rothamsted_dashboard_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add ajax.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Add inline container wrapper.
    $wrapper_id = Html::getUniqueId('search');
    $form['#attributes']['class'][] = 'rothamsted-search';
    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['inline-container'],
        'id' => $wrapper_id,
      ],
      '#attached' => [
        'library' => ['farm_rothamsted_dashboard/search'],
      ],
    ];

    // Define entity type search options.
    $entity_types = [
      'land_asset' => [
        'label' => $this->t('Field'),
        'help' => $this->t('Search by field name'),
      ],
      'plant_asset' => [
        'label' => $this->t('Crop asset'),
        'help' => $this->t('Search by plant asset name or plant type'),
      ],
      'experiment' => [
        'label' => $this->t('Experiment'),
        'help' => $this->t('Search by experiment name, code or researcher'),
      ],
    ];
    $entity_type_options = array_map(function($option) {
      return $option['label'];
    }, $entity_types);
    $default = 'land_asset';

    $selected_entity_type = $form_state->hasValue('entity_type') ? $form_state->getValue('entity_type') : $default;
    $form['wrapper']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#title_display' => 'visually_hidden',
      '#options' => $entity_type_options,
      '#default_value' => $default,
      '#ajax' => [
        'callback' => '::wrapperCallback',
        'wrapper' => $wrapper_id,
        'event' => 'change',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['wrapper']['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#title_display' => 'visually_hidden',
      '#attributes' => [
        'placeholder' => $entity_types[$selected_entity_type]['help'] ?? NULL,
      ],
      '#ajax' => [
        'callback' => '::resultsCallback',
        'wrapper' => 'search-results',
        'event' => 'change',
      ],
      '#size' => 30,
    ];

    $form['wrapper']['submit'] = [
      '#type' => 'submit',
      '#submit' => ['::searchCallback'],
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => '::resultsCallback',
      ],
    ];

    // Build search results.
    if ($search = $form_state->getValue('search')) {
      switch ($form_state->getValue('entity_type')) {
        case 'land_asset':
          $form['results'] = $this->getFieldResults($search);
          break;

        case 'plant_asset':
          $form['results'] = $this->getPlantResults($search);
          break;

        case 'experiment':
          $form['experiments'] = $this->getExperimentResults($search);
          $form['designs'] = $this->getDesignResults($search);
          $form['plans'] = $this->getPlanResults($search);
          break;
      }
    }

    return $form;
  }

  /**
   * Wrapper callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Render array.
   */
  public function wrapperCallback(array &$form, FormStateInterface $form_state) {
    return $form['wrapper'];
  }

  /**
   * Callback for search button.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function searchCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Results callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response.
   */
  public function resultsCallback(array &$form, FormStateInterface $form_state) {
    $dialog_options = [
      'modal' => TRUE,
      'width' => 800,
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand('Search result', $form, $dialog_options));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not implemented.
  }

  /**
   * Helper function to return field results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Results render array.
   */
  public function getFieldResults(string $query): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $asset_query = $entity_type_manager->getStorage('asset')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('type', 'land')
      ->condition('land_type', 'field')
      ->condition('name', $query, 'CONTAINS')
      ->sort('name', 'ASC');
    if (!$ids = $asset_query->execute()) {
      return $this->noResults('Fields', $query);
    }

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '@count search result: %query',
      '@count search results: %query',
      [
        '%query' => $query,
      ],
    );
    $render['results'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Name'),
        ],
        [
          'data' => $this->t('Status'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\asset\Entity\AssetInterface[] $entities */
    $entities = $entity_type_manager->getStorage('asset')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $render['results']['#rows'][$entity->id()] = [
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $entity->get('status')->view(['label' => 'visually_hidden']),
        ],
      ];
    }
    return $render;
  }

  /**
   * Helper function to return plant asset results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Results render array.
   */
  public function getPlantResults(string $query): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $asset_query = $entity_type_manager->getStorage('asset')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('type', 'plant')
      ->sort('name', 'ASC');
    $or = $asset_query->orConditionGroup()
      ->condition('name', $query, 'CONTAINS')
      ->condition('plant_type.entity.name', $query, 'CONTAINS')
      ->condition('season.entity.name', $query, 'CONTAINS');
    $asset_query->condition($or);
    if (!$ids = $asset_query->execute()) {
      return $this->noResults('Crop assets', $query);
    }

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '@count search result: %query',
      '@count search results: %query',
      [
        '%query' => $query,
      ],
    );
    $render['results'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Name'),
        ],
        [
          'data' => $this->t('Plant type'),
        ],
        [
          'data' => $this->t('Status'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\asset\Entity\AssetInterface[] $entities */
    $entities = $entity_type_manager->getStorage('asset')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $render['results']['#rows'][$entity->id()] = [
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $entity->get('plant_type')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('status')->view(['label' => 'visually_hidden']),
        ],
      ];
    }
    return $render;
  }

  /**
   * Helper function to return plant asset results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Results render array.
   */
  public function getExperimentResults(string $query): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $experiment_query = $entity_type_manager->getStorage('rothamsted_experiment')->getQuery()
      ->accessCheck(TRUE)
      ->sort('name', 'ASC');
    $or = $experiment_query->orConditionGroup()
      ->condition('name', $query, 'CONTAINS')
      ->condition('abbreviation', $query, 'CONTAINS')
      ->condition('code', $query, 'CONTAINS')
      ->condition('researcher.entity.name', $query, 'CONTAINS');
    $experiment_query->condition($or);
    if (!$ids = $experiment_query->execute()) {
      return $this->noResults('Experiment', $query);
    }

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '<strong>Experiments:</strong> @count result',
      '<strong>Experiments</strong> @count results',
      [
        '%query' => $query,
      ],
    );
    $render['results'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Experiment'),
        ],
        [
          'data' => $this->t('Abbreviation'),
        ],
        [
          'data' => $this->t('Code'),
        ],
        [
          'data' => $this->t('Researchers'),
        ],
        [
          'data' => $this->t('Status'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperiment[] $entities */
    $entities = $entity_type_manager->getStorage('rothamsted_experiment')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $render['results']['#rows'][$entity->id()] = [
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $entity->get('abbreviation')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('code')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('researcher')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('status')->view(['label' => 'visually_hidden']),
        ],
      ];
    }
    return $render;
  }

  /**
   * Helper function to return design results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Results render array.
   */
  public function getDesignResults(string $query): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $experiment_query = $entity_type_manager->getStorage('rothamsted_design')->getQuery()
      ->accessCheck(TRUE)
      ->sort('name', 'ASC');
    $or = $experiment_query->orConditionGroup()
      ->condition('name', $query, 'CONTAINS')
      ->condition('description', $query, 'CONTAINS')
      ->condition('design_changes', $query, 'CONTAINS')
      ->condition('rotation_description', $query, 'CONTAINS')
      ->condition('rotation_phasing', $query, 'CONTAINS')
      ->condition('rotation_notes', $query, 'CONTAINS')
      ->condition('objective', $query, 'CONTAINS')
      ->condition('treatment', $query, 'CONTAINS')
      ->condition('dependent_variables', $query, 'CONTAINS')
      ->condition('hypothesis', $query, 'CONTAINS')
      ->condition('model', $query, 'CONTAINS')
      ->condition('notes', $query, 'CONTAINS')
      ->condition('layout_description', $query, 'CONTAINS')
      ->condition('statistician.entity.name', $query, 'CONTAINS');
    $experiment_query->condition($or);
    if (!$ids = $experiment_query->execute()) {
      return $this->noResults('Designs', $query);
    }

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '<strong>Designs:</strong> @count result',
      '<strong>Designs:</strong> @count results',
      [
        '%query' => $query,
      ],
    );
    $render['results'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Design'),
        ],
        [
          'data' => $this->t('Experiment'),
        ],
        [
          'data' => $this->t('Statisticians'),
        ],
        [
          'data' => $this->t('Start'),
        ],
        [
          'data' => $this->t('End'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface[] $entities */
    $entities = $entity_type_manager->getStorage('rothamsted_design')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $render['results']['#rows'][$entity->id()] = [
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $entity->get('experiment')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('statistician')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('start')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('end')->view(['label' => 'visually_hidden']),
        ],
      ];
    }
    return $render;
  }

  /**
   * Helper function to return experiment plan results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Results render array.
   */
  public function getPlanResults(string $query): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $experiment_query = $entity_type_manager->getStorage('plan')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'rothamsted_experiment')
      ->sort('name', 'ASC');
    $or = $experiment_query->orConditionGroup()
      ->condition('name', $query, 'CONTAINS')
      ->condition('abbreviation', $query, 'CONTAINS')
      ->condition('study_period_id', $query, 'CONTAINS')
      ->condition('cost_code', $query, 'CONTAINS')
      ->condition('deviations', $query, 'CONTAINS')
      ->condition('growing_conditions', $query, 'CONTAINS')
      ->condition('study_description', $query, 'CONTAINS')
      ->condition('current_phase', $query, 'CONTAINS')
      ->condition('notes', $query, 'CONTAINS');
    $experiment_query->condition($or);
    if (!$ids = $experiment_query->execute()) {
      return $this->noResults('Study plans', $query);
    }

    $caption = new PluralTranslatableMarkup(
      count($ids),
      '<strong>Study plans:</strong> @count result',
      '<strong>Study plans:</strong> @count results',
      [
        '%query' => $query,
      ],
    );
    $render['results'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => [
        [
          'data' => $this->t('Study plan'),
        ],
        [
          'data' => $this->t('Study Period ID'),
        ],
        [
          'data' => $this->t('Design'),
        ],
        [
          'data' => $this->t('Location'),
        ],
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface[] $entities */
    $entities = $entity_type_manager->getStorage('plan')->loadMultiple($ids);
    foreach ($entities as $entity) {
      $render['results']['#rows'][$entity->id()] = [
        [
          'data' => $entity->toLink($entity->label()),
        ],
        [
          'data' => $entity->get('study_period_id')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('experiment_design')->view(['label' => 'visually_hidden']),
        ],
        [
          'data' => $entity->get('location')->view(['label' => 'visually_hidden']),
        ],
      ];
    }
    return $render;
  }

  /**
   * Helper function to return no results.
   *
   * @param string $label
   *   Search label.
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Render array.
   */
  public function noResults(string $label, string $query): array {
    return [
      '#type' => 'table',
      '#caption' => [
        '#markup' => $this->t('<strong>@label:</strong> No results for search query "@query"', ['@label' => $label, '@query' => $query]),
      ],
    ];
  }

}
