<?php

namespace Drupal\simplenews_demo\Plugin\simplenews\RecipientHandler;

use Drupal\simplenews\SubscriberInterface;
use Drupal\simplenews\Plugin\simplenews\RecipientHandler\RecipientHandlerEntityBase;

/**
 * This handler sends to all subscribers with the specified role.
 *
 * @RecipientHandler(
 *   id = "simplenews_subscribers_by_role",
 *   title = @Translation("Subscribers by role")
 * )
 */
class RecipientHandlerSubscribersByRole extends RecipientHandlerEntityBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));

    $element['role'] = [
      '#type' => 'select',
      '#title' => t('Role'),
      '#default_value' => $this->configuration['role'] ?? NULL,
      '#options' => $roles,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery() {
    return \Drupal::entityQuery('simplenews_subscriber')
      ->condition('status', SubscriberInterface::ACTIVE)
      ->condition('subscriptions', $this->getNewsletterId())
      ->condition('subscriptions.status', SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED)
      ->condition('uid.entity.roles', $this->configuration['role']);
  }

}
