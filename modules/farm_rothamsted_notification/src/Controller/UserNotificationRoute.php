<?php

namespace Drupal\farm_rothamsted_notification\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * User notification controller.
 */
class UserNotificationRoute extends ControllerBase {

  /**
   * Simple controller endpoint to redirect user to their notification page.
   *
   * This is useful for sending a link in emails that is not user specific.
   */
  public function notificationRedirect() {
    return $this->redirect('farm_rothamsted_notification.user_notification_form', ['user' => $this->currentUser()->id()]);
  }

}
