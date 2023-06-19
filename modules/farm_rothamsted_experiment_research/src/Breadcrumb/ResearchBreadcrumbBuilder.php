<?php

namespace Drupal\farm_rothamsted_experiment_research\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Build research breadcrumbs.
 */
class ResearchBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    // Only apply to experiment plans.
    if ($route_match->getRouteName() == 'entity.plan.canonical') {
      $plan = $route_match->getParameter('plan');
      return $plan->bundle() == 'rothamsted_experiment';
    }

    // Apply to custom entity canonical routes.
    $routes = [
      'entity.rothamsted_design.canonical',
      'entity.rothamsted_experiment.canonical',
      'entity.rothamsted_program.canonical',
      'entity.rothamsted_research.canonical',
    ];
    return in_array($route_match->getRouteName(), $routes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path.parent', 'url.path.is_front']);

    // Add links based on the route.
    switch ($route_match->getRouteName()) {

      case 'entity.rothamsted_program.canonical':
        /** @var \Drupal\asset\Entity\AssetInterface $asset */
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Research Programs'), 'entity.rothamsted_program.collection'));
        break;

      case 'entity.rothamsted_experiment.canonical':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Research Programs'), 'entity.rothamsted_program.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Experiments'), 'entity.rothamsted_experiment.collection'));
        break;

      case 'entity.rothamsted_design.canonical':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Research Programs'), 'entity.rothamsted_program.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Experiments'), 'entity.rothamsted_experiment.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Designs'), 'entity.rothamsted_design.collection'));
        break;

      case 'entity.plan.canonical':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Research Programs'), 'entity.rothamsted_program.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Experiments'), 'entity.rothamsted_experiment.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Designs'), 'entity.rothamsted_design.collection'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Plans'), 'view.rothamsted_experiment_plan.page_research'));
        break;

      case 'entity.rothamsted_researcher.canonical':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Research Programs'), 'entity.rothamsted_program.collection'));
        break;

    }

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
