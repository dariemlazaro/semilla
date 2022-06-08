<?php

namespace Drupal\simplenews;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the subscriber entity type.
 */
class SubscriberViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['simplenews_subscriber']['edit_link'] = [
      'field' => [
        'title' => $this->t('Link to edit'),
        'help' => $this->t('Provide a simple link to edit the subscriber.'),
        'id' => 'subscriber_link_edit',
      ],
    ];

    $data['simplenews_subscriber']['delete_link'] = [
      'field' => [
        'title' => $this->t('Link to delete'),
        'help' => $this->t('Provide a simple link to delete the subscriber.'),
        'id' => 'subscriber_link_delete',
      ],
    ];

    // @todo Username obtained through custom plugin due to core issue.
    $data['simplenews_subscriber']['user_name'] = [
      'real field' => 'uid',
      'field' => [
        'title' => $this->t('Username'),
        'help' => $this->t("Provide a simple link to the subscriber's user account."),
        'id' => 'simplenews_user_name',
      ],
    ];

    $data['simplenews_subscriber__subscriptions']['subscriptions_status']['filter'] = [
      'id' => 'in_operator',
      'options callback' => 'simplenews_subscriber_status_list',
    ];

    $data['simplenews_subscriber__subscriptions']['subscriptions_target_id']['filter'] = [
      'id' => 'in_operator',
      'options callback' => 'simplenews_newsletter_list',
    ];

    return $data;
  }

}
