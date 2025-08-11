<?php

declare(strict_types=1);

namespace Drupal\farm_rothamsted_researcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that provides routes for the current user researcher.
 */
class CurrentUserResearcher extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Redirect to the current user's researcher entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to view the researcher entity.
   */
  public function redirectToResearcherProfile() {

    // Query for the current user's researcher profile.
    $current_user = $this->currentUser();
    $researchers = $this->entityTypeManager->getStorage('rothamsted_researcher')->loadByProperties([
      'farm_user' => $current_user->id(),
    ]);

    // If a researcher is found, redirect to its canonical page.
    if (!empty($researchers)) {
      $researcher = reset($researchers);
      return new RedirectResponse($researcher->toUrl()->toString());
    }

    // If no researcher found, redirect and show an error message.
    $this->messenger()->addWarning($this->t('No researcher profile found for the current user.'));
    return new RedirectResponse((new Url('entity.user.canonical', ['user' => $current_user->id()]))->toString());
  }

}
