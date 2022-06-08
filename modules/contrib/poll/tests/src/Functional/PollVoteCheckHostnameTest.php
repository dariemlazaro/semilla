<?php

namespace Drupal\Tests\poll\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Check that users and anonymous users from specified ip-address can only vote once.
 *
 * @group poll
 */
class PollVoteCheckHostnameTest extends PollTestBase {

  function setUp() {
    parent::setUp();

    // Allow anonymous users to vote on polls.
    user_role_change_permissions(AccountInterface::ANONYMOUS_ROLE, array(
      // 'vote on polls' => TRUE,
      'cancel own vote' => TRUE,
      'access polls' => TRUE,
    ));

    $this->poll->setAnonymousVoteAllow(TRUE)->save();
  }

  /**
   * Checks that anonymous users with the same IP address can only vote once.
   *
   * Also checks that authenticated users can only vote once, even when the
   * user's IP address has changed.
   */
  function testHostnamePollVote() {

    $web_user2 = $this->drupalCreateUser(array('access polls'));
    // Login User1.
    $this->drupalLogin($this->web_user);

    $edit = array(
      'choice' => '1',
    );

    //  $this->web_user->getUserName();
    // User1 vote on Poll.
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText(t('Your vote has been recorded.'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 1)));

    // Check to make sure User1 cannot vote again.
    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), $this->web_user->getAccountName() . " is not able to vote again.");
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(!empty($elements), "'Cancel vote' button appears.");

    // Logout User1.
    $this->drupalLogout();

    // Fill the page cache by requesting the poll.
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'MISS', 'Page was cacheable but was not in the cache.');
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'HIT', 'Page was cached.');

    // Anonymous user vote on Poll.
    $this->drupalPostForm(NULL, $edit, t('Vote'));
    $this->assertText(t('Your vote has been recorded.'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 2)));
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(!empty($elements), "'Cancel vote' button appears.");

    // Check to make sure Anonymous user cannot vote again.
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertNull($this->drupalGetHeader('x-drupal-cache'), 'Page was not cacheable.');
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), "Anonymous is not able to vote again.");
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(!empty($elements), "'Cancel vote' button appears.");

    // Login User2.
    $this->drupalLogin($web_user2);

    // User2 vote on poll.
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText(t('Your vote has been recorded.'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 3)));
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(empty($elements), "'Cancel vote' button does not appear.");

    // Logout User2.
    $this->drupalLogout();

    // Change host name for anonymous users.
    \Drupal::database()->update('poll_vote')
      ->fields(array(
        'hostname' => '123.456.789.1',
      ))
      ->condition('hostname', '', '<>')
      ->execute();

    // Check to make sure Anonymous user can vote again with a new session after
    // a hostname change.
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'HIT', 'Cached page return.');
    $this->drupalPostForm(NULL, $edit, t('Vote'));
    $this->assertText(t('Your vote has been recorded.'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 4)));
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(!empty($elements), "'Cancel vote' button appears.");

    // Check to make sure Anonymous user cannot vote again with a new session,
    // and that the vote from the previous session cannot be cancelled. This
    // can't use drupalLogout() because we aren't actually logged in, so we
    // manually unset the session cookie.
    $this->getSession()->setCookie($this->getSessionName());
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertEqual($this->drupalGetHeader('x-drupal-cache'), 'HIT', 'Page was cacheable but was not in the cache.');
    $this->drupalPostForm(NULL, $edit, t('Vote'));
    $this->assertText(t('Your vote for this poll has already been submitted.'));
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(empty($elements), "'Cancel vote' button does not appear.");

    // Login User1.
    $this->drupalLogin($this->web_user);

    // Check to make sure User1 still cannot vote even after hostname changed.
    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), $this->web_user->getAccountName() . " is not able to vote again.");
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(!empty($elements), "'Cancel vote' button appears.");
  }
}
