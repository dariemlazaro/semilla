<?php

namespace Drupal\simplenews\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\OptGroup;
use Drupal\simplenews\SubscriptionWidgetInterface;

/**
 * Plugin implementation of the 'simplenews_subscription_select' widget.
 *
 * @FieldWidget(
 *   id = "simplenews_subscription_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "simplenews_subscription"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SubscriptionWidget extends OptionsButtonsWidget implements SubscriptionWidgetInterface {

  /**
   * IDs of the newsletters available for selection.
   *
   * @var string[]
   */
  protected $newsletterIds;

  /**
   * {@inheritdoc}
   */
  public function setAvailableNewsletterIds(array $newsletter_ids = NULL) {
    $this->newsletterIds = array_keys(simplenews_newsletter_get_visible());
    if (isset($newsletter_ids)) {
      $this->newsletterIds = array_intersect($newsletter_ids, $this->newsletterIds);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableNewsletterIds() {
    if (!isset($this->newsletterIds)) {
      $this->setAvailableNewsletterIds();
    }
    return $this->newsletterIds;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    return array_intersect_key(parent::getOptions($entity), array_flip($this->getAvailableNewsletterIds()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // Copy parent behavior but also check the status property.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));
    $selected_options = [];
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if ($item->status == SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED && isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }
    return $selected_options;
  }

  /**
   * {@inheritdoc}
   */
  public function extractNewsletterIds(array $form_state_value, $selected = TRUE) {
    $selected_ids = array_map(function ($item) {
      return $item['target_id'];
    }, $form_state_value);
    return $selected ? $selected_ids : array_diff($this->getAvailableNewsletterIds(), $selected_ids);
  }

}
