<?php

namespace Drupal\farm_rothamsted_dashboard\Form;

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
    $form['#attributes']['class'][] = 'rothamsted-search';
    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['inline-container'],
      ],
      '#attached' => [
        'library' => ['farm_rothamsted_dashboard/search'],
      ],
    ];

    $form['wrapper']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#title_display' => 'visually_hidden',
      '#options' => [
        'land_asset' => $this->t('Field'),
        'plant_asset' => $this->t('Crop asset'),
        'experiment' => $this->t('Experiment'),
      ],
    ];

    $form['wrapper']['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#title_display' => 'visually_hidden',
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
          $form['results'] = $this->getExperimentResults($search);
          break;
      }
    }

    return $form;
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
      return $this->noResults($query);
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
      return $this->noResults($query);
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
      return $this->noResults($query);
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
   * Helper function to return no results.
   *
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Render array.
   */
  public function noResults(string $query): array {
    return [
      '#type' => 'table',
      '#caption' => [
        '#markup' => $this->t('No results for search query: %query', ['%query' => $query]),
      ],
    ];
  }

}
