<?php

namespace Drupal\simplenews;

use Drupal\Core\Field\WidgetInterface;

/**
 * Defines a widget used for the subscriptions field of a Subscriber.
 */
interface SubscriptionWidgetInterface extends WidgetInterface {

  /**
   * Set the newsletters available for selection.
   *
   * @param string[] $newsletter_ids
   *   Indexed array of newsletter IDs.
   */
  public function setAvailableNewsletterIds(array $newsletter_ids);

  /**
   * Returns the newsletters available to select from.
   *
   * @return string[]
   *   The newsletter IDs available to select from, as an indexed array.
   */
  public function getAvailableNewsletterIds();

  /**
   * Returns the IDs of the selected or deselected newsletters.
   *
   * @param array $form_state_value
   *   The value of the widget as returned by FormStateInterface::getValue().
   * @param bool $selected
   *   Whether to extract selected (TRUE) or deselected (FALSE) newsletter IDs.
   *
   * @return string[]
   *   IDs of selected/deselected newsletters.
   */
  public function extractNewsletterIds(array $form_state_value, $selected);

}
