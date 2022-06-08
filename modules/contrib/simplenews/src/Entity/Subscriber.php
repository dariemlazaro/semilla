<?php

namespace Drupal\simplenews\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simplenews\SubscriberInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the simplenews subscriber entity.
 *
 * @ContentEntityType(
 *   id = "simplenews_subscriber",
 *   label = @Translation("Simplenews subscriber"),
 *   handlers = {
 *     "storage" = "Drupal\simplenews\Subscription\SubscriptionStorage",
 *     "access" = "Drupal\simplenews\SubscriberAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simplenews\Form\SubscriberForm",
 *       "default" = "Drupal\simplenews\Form\SubscriberForm",
 *       "account" = "Drupal\simplenews\Form\SubscriptionsAccountForm",
 *       "block" = "Drupal\simplenews\Form\SubscriptionsBlockForm",
 *       "page" = "Drupal\simplenews\Form\SubscriptionsPageForm",
 *       "delete" = "Drupal\simplenews\Form\SubscriberDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\simplenews\SubscriberViewsData"
 *   },
 *   base_table = "simplenews_subscriber",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "mail"
 *   },
 *   field_ui_base_route = "simplenews.settings_subscriber",
 *   admin_permission = "administer simplenews subscriptions",
 *   links = {
 *     "edit-form" = "/admin/people/simplenews/edit/{simplenews_subscriber}",
 *     "delete-form" = "/admin/people/simplenews/delete/{simplenews_subscriber}",
 *   },
 *   token_type = "simplenews-subscriber"
 * )
 */
class Subscriber extends ContentEntityBase implements SubscriberInterface {

  /**
   * Whether currently copying field values to corresponding User.
   *
   * @var bool
   */
  protected static $syncing;

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->set('message', $message);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value == SubscriberInterface::ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status ? SubscriberInterface::ACTIVE : SubscriberInterface::INACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMail($mail) {
    $this->set('mail', $mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    $uid = $this->getUserId();
    if ($uid && ($user = User::load($uid))) {
      return $user;
    }
    elseif ($mail = $this->getMail()) {
      return user_load_by_mail($mail) ?: NULL;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->set('langcode', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function fillFromAccount(AccountInterface $account) {
    if (static::$syncing) {
      return $this;
    }

    static::$syncing = TRUE;
    $this->set('uid', $account->id());
    $this->setMail($account->getEmail());
    $this->setLangcode($account->getPreferredLangcode());
    $this->setStatus($account->isActive());

    // Copy values for shared fields to existing subscriber.
    foreach ($this->getUserSharedFields($account) as $field_name) {
      $this->set($field_name, $account->get($field_name)->getValue());
    }

    static::$syncing = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function copyToAccount(AccountInterface $account) {
    // Copy values for shared fields to existing user.
    if (!static::$syncing && ($fields = $this->getUserSharedFields($account))) {
      static::$syncing = TRUE;
      foreach ($fields as $field_name) {
        $account->set($field_name, $this->get($field_name)->getValue());
      }
      if (!$account->isNew()) {
        $account->save();
      }
      static::$syncing = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChanges() {
    return unserialize($this->get('changes')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setChanges($changes) {
    $this->set('changes', serialize($changes));
  }

  /**
   * {@inheritdoc}
   */
  public function isSubscribed($newsletter_id) {
    foreach ($this->subscriptions as $item) {
      if ($item->target_id == $newsletter_id) {
        return $item->status == SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnsubscribed($newsletter_id) {
    foreach ($this->subscriptions as $item) {
      if ($item->target_id == $newsletter_id) {
        return $item->status == SIMPLENEWS_SUBSCRIPTION_STATUS_UNSUBSCRIBED;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscription($newsletter_id) {
    foreach ($this->subscriptions as $item) {
      if ($item->target_id == $newsletter_id) {
        return $item;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribedNewsletterIds() {
    $ids = [];
    foreach ($this->subscriptions as $item) {
      if ($item->status == SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED) {
        $ids[] = $item->target_id;
      }
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe($newsletter_id, $status = SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED, $source = 'unknown', $timestamp = REQUEST_TIME) {
    if ($subscription = $this->getSubscription($newsletter_id)) {
      $subscription->status = $status;
    }
    else {
      $data = [
        'target_id' => $newsletter_id,
        'status' => $status,
        'source' => $source,
        'timestamp' => $timestamp,
      ];
      $this->subscriptions->appendItem($data);
    }
    if ($status == SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED) {
      \Drupal::moduleHandler()->invokeAll('simplenews_subscribe', [$this, $newsletter_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unsubscribe($newsletter_id, $source = 'unknown', $timestamp = REQUEST_TIME) {
    if ($subscription = $this->getSubscription($newsletter_id)) {
      $subscription->status = SIMPLENEWS_SUBSCRIPTION_STATUS_UNSUBSCRIBED;
    }
    else {
      $data = [
        'target_id' => $newsletter_id,
        'status' => SIMPLENEWS_SUBSCRIPTION_STATUS_UNSUBSCRIBED,
        'source' => $source,
        'timestamp' => $timestamp,
      ];
      $this->subscriptions->appendItem($data);
    }
    // Clear eventually existing mail spool rows for this subscriber.
    \Drupal::service('simplenews.spool_storage')->deleteMails(['snid' => $this->id(), 'newsletter_id' => $newsletter_id]);

    \Drupal::moduleHandler()->invokeAll('simplenews_unsubscribe', [$this, $newsletter_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Copy values for shared fields to existing user.
    if ($user = $this->getUser()) {
      $this->copyToAccount($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Fill from a User account with matching uid or email.
    if ($user = $this->getUser()) {
      $this->fillFromAccount($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If there is not already a linked user, fill from an account with
    // matching uid or email.
    if (!$this->isNew() && !$this->getUserId() && $user = $this->getUser()) {
      $this->fillFromAccount($user);
    }
  }

  /**
   * Identifies configurable fields shared with a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to match fields against.
   *
   * @return string[]
   *   An indexed array of the names of each field for which there is also a
   *   field on the given user with the same name and type.
   */
  protected function getUserSharedFields(UserInterface $user) {
    $field_names = [];

    if (\Drupal::config('simplenews.settings')->get('subscriber.sync_fields')) {
      // Find any fields sharing name and type.
      foreach ($this->getFieldDefinitions() as $field_definition) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        $field_name = $field_definition->getName();
        $user_field = $user->getFieldDefinition($field_name);
        if ($field_definition->getTargetBundle() && isset($user_field) && $user_field->getType() == $field_definition->getType()) {
          $field_names[] = $field_name;
        }
      }
    }

    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Subscriber ID'))
      ->setDescription(t('Primary key: Unique subscriber ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The subscriber UUID.'))
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Boolean indicating the status of the subscriber.'))
      ->setDefaultValue(TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t("The subscriber's email address."))
      ->setSetting('default_value', '')
      ->setRequired(TRUE)
      ->addConstraint('UniqueField', [])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The corresponding user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t("The subscriber's preferred language."));

    $fields['changes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Changes'))
      ->setDescription(t('Contains the requested subscription changes.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the subscriber was created.'));

    $fields['subscriptions'] = BaseFieldDefinition::create('simplenews_subscription')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setLabel(t('Subscriptions'))
      ->setDescription(t('Check the newsletters you want to subscribe to. Uncheck the ones you want to unsubscribe from.'))
      ->setSetting('target_type', 'simplenews_newsletter')
      ->setDisplayOptions('form', [
        'type' => 'simplenews_subscription_select',
        'weight' => '0',
        'settings' => [],
        'third_party_settings' => [],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByMail($mail, $create = FALSE, $default_langcode = NULL) {
    $subscriber = FALSE;
    if ($mail) {
      $subscribers = \Drupal::entityTypeManager()->getStorage('simplenews_subscriber')->loadByProperties(['mail' => $mail]);
      $subscriber = reset($subscribers);
    }

    if ($create && !$subscriber) {
      $subscriber = static::create(['mail' => $mail]);
      if ($default_langcode) {
        $subscriber->setLangcode($default_langcode);
      }
    }
    return $subscriber;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUid($uid, $create = FALSE) {
    $subscriber = FALSE;
    if ($uid) {
      $subscribers = \Drupal::entityTypeManager()->getStorage('simplenews_subscriber')->loadByProperties(['uid' => $uid]);
      $subscriber = reset($subscribers);
    }

    if ($create && !$subscriber) {
      $subscriber = static::create(['uid' => $uid]);
    }
    return $subscriber;
  }

}
