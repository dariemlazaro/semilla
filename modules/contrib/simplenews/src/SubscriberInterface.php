<?php

namespace Drupal\simplenews;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Simplenews subscriber entity interface.
 */
interface SubscriberInterface extends ContentEntityInterface {

  /**
   * Subscriber is inactive.
   */
  const INACTIVE = 0;

  /**
   * Subscriber is active.
   */
  const ACTIVE = 1;

  /**
   * Returns if the subscriber is active or not.
   *
   * @return bool
   *   The subscribers status.
   */
  public function getStatus();

  /**
   * Sets the status of the subscriber.
   *
   * @param bool $status
   *   The subscribers status.
   */
  public function setStatus($status);

  /**
   * Returns the subscribers email address.
   *
   * @return string
   *   The subscribers email address.
   */
  public function getMail();

  /**
   * Sets the subscribers email address.
   *
   * @param string $mail
   *   The subscribers email address.
   */
  public function setMail($mail);

  /**
   * Returns corresponding user ID.
   *
   * @return int
   *   The corresponding user ID.
   */
  public function getUserId();

  /**
   * Returns corresponding User object, if any.
   *
   * @return \Drupal\user\UserInterface|null
   *   The corresponding User object, or NULL if the subscriber is not synced
   *   with a user.
   */
  public function getUser();

  /**
   * Returns the lang code.
   *
   * @return string
   *   The subscribers lang code.
   */
  public function getLangcode();

  /**
   * Sets the lang code.
   *
   * @param string $langcode
   *   The subscribers lang code.
   */
  public function setLangcode($langcode);

  /**
   * Fill values from a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to fill from.
   *
   * @return $this
   */
  public function fillFromAccount(AccountInterface $account);

  /**
   * Copy values to a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to copy to.
   */
  public function copyToAccount(AccountInterface $account);

  /**
   * Returns the changes.
   *
   * @return array
   *   The subscriber changes.
   */
  public function getChanges();

  /**
   * Sets the changes.
   *
   * @param string $changes
   *   The subscriber changes.
   */
  public function setChanges($changes);

  /**
   * Check if the subscriber has an active subscription to a certain newsletter.
   *
   * @param string $newsletter_id
   *   The ID of a newsletter.
   *
   * @return bool
   *   Returns TRUE if the subscriber has the subscription, otherwise FALSE.
   */
  public function isSubscribed($newsletter_id);

  /**
   * Check if the subscriber has an inactive subscription to a given newsletter.
   *
   * @param string $newsletter_id
   *   The ID of a newsletter.
   *
   * @return bool
   *   TRUE if the subscriber has the inactive subscription, otherwise FALSE.
   */
  public function isUnsubscribed($newsletter_id);

  /**
   * Returns the subscription to a given newsletter..
   *
   * @param string $newsletter_id
   *   The ID of a newsletter.
   *
   * @return \Drupal\simplenews\Plugin\Field\FieldType\SubscriptionItem
   *   The subscription item if the subscriber is subscribed, otherwise FALSE.
   */
  public function getSubscription($newsletter_id);

  /**
   * Get the ids of all subscribed newsletters.
   *
   * @return array
   *   Returns the ids of all newsletters the subscriber is subscribed.
   */
  public function getSubscribedNewsletterIds();

  /**
   * Add a subscription to a certain newsletter to the subscriber.
   *
   * @param string $newsletter_id
   *   The ID of a newsletter.
   * @param int $status
   *   The status of the subscription.
   * @param string $source
   *   The source where the subscription comes from.
   * @param int $timestamp
   *   The timestamp of when the subscription was added.
   */
  public function subscribe($newsletter_id, $status = SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED, $source = 'unknown', $timestamp = REQUEST_TIME);

  /**
   * Delete a subscription to a certain newsletter of the subscriber.
   *
   * @param string $newsletter_id
   *   The ID of a newsletter.
   * @param string $source
   *   The source where the subscription comes from.
   * @param int $timestamp
   *   The timestamp of when the subscription was added.
   */
  public function unsubscribe($newsletter_id, $source = 'unknown', $timestamp = REQUEST_TIME);

  /**
   * Load a simplenews newsletter subscriber object by mail.
   *
   * @param string $mail
   *   Subscriber e-mail address.
   * @param bool $create
   *   (optional) Whether to create a new subscriber if none exists. Defaults
   *   to TRUE.
   * @param string $default_langcode
   *   (optional) Langcode to set if a new subscriber is created.
   *
   * @return \Drupal\simplenews\SubscriberInterface
   *   Newsletter subscriber entity, FALSE if subscriber does not exist.
   */
  public static function loadByMail($mail, $create = FALSE, $default_langcode = NULL);

  /**
   * Load a simplenews newsletter subscriber object by uid.
   *
   * @param int $uid
   *   Subscriber user id.
   * @param bool $create
   *   (optional) Whether to create a new subscriber if none exists. Defaults
   *   to TRUE.
   *
   * @return \Drupal\simplenews\SubscriberInterface
   *   Newsletter subscriber entity, FALSE if subscriber does not exist.
   */
  public static function loadByUid($uid, $create = FALSE);

}
