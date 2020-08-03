<?php

namespace Drupal\pug\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class PugAccountSettingSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change form for the entity.user.admin_form route
    // to Drupal\pug\Form\PugAccountSettingsForm
    // First, we need to act only on the entity.user.admin_form route.
    if ($route = $collection->get('entity.user.admin_form')) {
      // Next, we need to set the value for _form to the form we have created.
      $route->setDefault('_form', 'Drupal\pug\Form\PugAccountSettingsForm');
    }
  }

}
