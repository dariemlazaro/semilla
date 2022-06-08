<?php

namespace Drupal\Tests\simplenews\Functional;

use Drupal\Component\Utility\Html;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests crucial aspects of Subscriber fieldability and User field sync.
 *
 * @group simplenews
 */
class SimplenewsPersonalizationFormsTest extends SimplenewsTestBase {
  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addField('string', 'field_shared', 'user');
    $this->addField('string', 'field_shared', 'simplenews_subscriber');

    Role::load('anonymous')
      ->grantPermission('subscribe to newsletters')
      ->grantPermission('access user profiles')
      ->save();
    Role::load('authenticated')
      ->grantPermission('subscribe to newsletters')
      ->save();

    $this->admin = $this->drupalCreateUser([
      'administer users',
    ]);
  }

  /**
   * Subscribe then register: fields updated, subscription remains unconfirmed.
   */
  public function testSynchronizeSubscribeRegister() {
    $email = $this->randomEmail();

    // Subscribe.
    $this->subscribe('default', $email, ['field_shared[0][value]' => $this->randomString(10)]);

    // Register.
    $new_value = $this->randomString(20);
    $uid = $this->registerUser($email, ['field_shared[0][value]' => $new_value]);

    // Assert fields are updated.
    $this->drupalGet("user/$uid");
    $this->assertText(Html::escape($new_value));

    // Assert subscription remains unconfirmed.
    $subscriber = $this->getLatestSubscriber();
    $this->assertEqual($subscriber->subscriptions->get(0)->status, SIMPLENEWS_SUBSCRIPTION_STATUS_UNCONFIRMED);
  }

  /**
   * Register then subscribe: fields updated.
   */
  public function testSynchronizeRegisterSubscribe() {
    $email = $this->randomEmail();

    // Register.
    $uid = $this->registerUser($email, ['field_shared[0][value]' => $this->randomString(10)]);
    $user = User::load($uid);

    // Subscribe anonymous with verification disabled.
    $this->config('simplenews.settings')
      ->set('subscription.skip_verification', TRUE)
      ->save();
    $new_value = $this->randomString(20);
    $this->subscribe('default', $email, ['field_shared[0][value]' => $new_value]);

    // Assert fields are updated.
    $this->resetPassLogin($user);
    $this->drupalGet("user/$uid");
    $this->assertText(Html::escape($new_value));
  }

  /**
   * Subscribe, check no user is created.
   */
  public function testSubscribeRequestPassword() {
    $email = $this->randomEmail();
    $this->subscribe([], $email);
    $this->assertFalse(user_load_by_mail($email));
  }

  /**
   * Disable account, subscriptions inactive.
   */
  public function testDisableAccount() {
    $email = $this->randomEmail();

    // Register account.
    $uid = $this->registerUser($email);

    // Subscribe.
    $this->resetPassLogin(User::load($uid));
    $this->subscribe('default', NULL, [], $uid);
    $this->drupalLogout();

    // Disable account.
    $this->drupalLogin($this->admin);
    $this->drupalGet("user/$uid/cancel");
    $this->submitForm([], 'Cancel account');

    // Assert subscriber is inactive.
    $subscriber = $this->getLatestSubscriber();
    $this->assertFalse($subscriber->getStatus());
  }

  /**
   * Delete account, subscriptions deleted.
   */
  public function testDeleteAccount() {
    $email = $this->randomEmail();

    // Register account.
    $uid = $this->registerUser($email);

    // Subscribe.
    $this->subscribe('default', $email);

    // Delete account.
    $this->drupalLogin($this->admin);
    $this->drupalGet("user/$uid/cancel");
    $this->submitForm(['user_cancel_method' => 'user_cancel_reassign'], 'Cancel account');

    // Assert subscriptions are deleted.
    $subscriber = $this->getLatestSubscriber();
    $this->assertNull($subscriber, 'No subscriber found');
  }

  /**
   * Blocked account subscribes, display message.
   */
  public function testBlockedSubscribe() {
    $email = $this->randomEmail();

    // Register account.
    $uid = $this->registerUser($email);

    // Block account.
    $this->drupalLogin($this->admin);
    $this->drupalGet("user/$uid/edit");
    $this->submitForm(['status' => 0], 'Save');
    $this->drupalLogout();

    // Attempt subscribe and assert "blocked" message.
    $this->subscribe('default', $email);
    $this->assertRaw(t('The email address %mail belongs to a blocked user.', ['%mail' => $email]));
  }

}
