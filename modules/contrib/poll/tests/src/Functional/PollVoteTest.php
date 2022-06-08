<?php

namespace Drupal\Tests\poll\Functional;

use Drupal\Core\Database\Database;
use Drupal\poll\PollInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests voting on a poll.
 *
 * @group poll
 */
class PollVoteTest extends PollTestBase {

  /**
   * Tests voting on a poll.
   */
  function testPollVote() {

    $this->drupalLogin($this->web_user);

    // Record a vote for the first choice.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText('Your vote has been recorded.');
    $this->assertText('Total votes: 1');
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel your vote' button appears.");

//    $this->drupalGet('poll/' . $this->poll->id() . '/votes');
//    $this->assertText(t('This table lists all the recorded votes for this poll. If anonymous users are allowed to vote, they will be identified by the IP address of the computer they used when they voted.'), 'Vote table text.');
//    $options = $this->poll->getOptions();
//    debug($options);

   // $this->assertText($this->poll->getOptions()[0], 'Vote recorded');

    // Ensure poll listing page has correct number of votes.
//    $this->drupalGet('poll');
//    $this->assertText($this->poll->label(), 'Poll appears in poll list.');
//    $this->assertText('1 vote', 'Poll has 1 vote.');

    // Cancel a vote.
    $this->drupalPostForm('poll/' . $this->poll->id(), array(), t('Cancel vote'));
    $this->assertText('Your vote was cancelled.');
    $this->assertNoText('Cancel your vote');

//    $this->drupalGet('poll/' . $this->poll->id() . '/votes');
//    $this->assertNoText($choices[0], 'Vote cancelled');

    // Ensure poll listing page has correct number of votes.
//    $this->drupalGet('poll');
//    $this->assertText($title, 'Poll appears in poll list.');
//    $this->assertText('0 votes', 'Poll has 0 votes.');

    // Log in as a user who can only vote on polls.
//    $this->drupalLogout();
//    $this->drupalLogin($restricted_vote_user);

    // Empty vote on a poll.
    $this->drupalPostForm('poll/' . $this->poll->id(), [], t('Vote'));
    $this->assertText('Your vote could not be recorded because you did not select any of the choices.');
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(isset($elements[0]), "'Vote' button appears.");

    // Vote on a poll.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText('Your vote has been recorded.');
    $this->assertText('Total votes: 1');
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), "'Cancel your vote' button does not appear.");

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/content/poll');
    $this->assertText($this->poll->label());

    $assert_session = $this->assertSession();

    // Test for the overview page.
    $assert_session->elementContains('css', 'tbody tr:nth-child(1) td:nth-child(2)', 'Yes');
    $assert_session->elementContains('css', 'tbody tr:nth-child(1) td:nth-child(3)', 'Off');

    // Edit the poll.
    $this->clickLink($this->poll->label());
    $this->clickLink('Edit');

    // Add the runtime date and allow anonymous to vote.
    $edit = array(
      'runtime' => 172800,
      'anonymous_vote_allow[value]' => TRUE,
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that editing was successful.
    $this->assertText('The poll ' . $this->poll->label() . ' has been updated.');

    // Check if the active label is correct.
    $date = \Drupal::service('date.formatter')->format($this->poll->getCreated() + 172800, 'short');
    $output = 'Yes (until ' . rtrim(strstr($date, '-', TRUE)) . ')';
    $assert_session->elementContains('css', 'tbody tr:nth-child(1) td:nth-child(2)', $output);

    // Check if allow anonymous voting is on.
    $assert_session->elementContains('css', 'tbody tr:nth-child(1) td:nth-child(3)', 'On');

    // Check the number of total votes.
    $assert_session->elementContains('css', 'tbody tr:nth-child(1) td:nth-child(5)', '1');

    // Add permissions to anonymous user to view polls.
    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access polls');
    $anonymous_role->save();

    // Let the anonymous user to vote.
    $this->drupalLogout();
    $edit = ['choice' => '1'];
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));

    // Login as admin and check the number of total votes on the overview page.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/content/poll');
    $xpath = "//tr[1]/td[@class='views-field views-field-votes']";
    $this->assertFieldByXPath($xpath, 2);

    // Cancel the vote from the user, ensure that backend updates.
    $this->drupalLogin($this->web_user);
    $this->drupalPostForm('poll/' . $this->poll->id(), [], t('Cancel vote'));
    $this->assertText(t('Your vote was cancelled.'));

    // Login as admin and check the number of total votes on the overview page.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/content/poll');
    $xpath = "//tr[1]/td[@class='views-field views-field-votes']";
    $this->assertFieldByXPath($xpath, 1);

    // Test for the 'View results' button.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="View results"]');
    $this->assertTrue(!empty($elements), "'View results' button appears.");

    $this->drupalLogin($this->web_user);
    $this->drupalGet('poll/' . $this->poll->id());
    $elements = $this->xpath('//input[@value="View results"]');
    $this->assertTrue(empty($elements), "'View results' button doesn't appear.");
  }

  /**
   * Test closed poll with "Cancel vote" button.
   */
  public function testClosedPollVoteCancel() {
    /** @var PollInterface $poll */
    $poll = $this->pollCreate();
    $this->drupalLogin($this->web_user);
    $choices = $poll->choice->getValue();
    $this->drupalGet('poll/' . $poll->id());
    // Vote on a poll.
    $edit = array(
      'choice' => $choices[0]['target_id'],
    );
    $this->drupalPostForm(NULL, $edit, t('Vote'));
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel your vote' button appears.");
    // Close a poll.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('poll/' . $poll->id() . '/edit');
    $edit = [
      'status[value]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check closed poll with "Cancel vote" button.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('poll/' . $poll->id());
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertFalse(isset($elements[0]), "'Cancel your vote' button not appears.");
  }

  /**
   * Test that anonymous user just remove it's own vote.
   */
  public function testAnonymousCancelVote() {
    // Now grant anonymous users permission to view the polls, vote and delete
    // it's own vote.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('cancel own vote', 'access polls'));
    $this->poll->setAnonymousVoteAllow(TRUE)->save();
    $this->drupalLogout();
    // First anonymous user votes.
    $edit = array(
      'choice' => '1',
    );
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));

    // Change the IP of first user.
    Database::getConnection()->update('poll_vote')
      ->fields(array('hostname' => '240.0.0.1'))
      ->condition('uid', \Drupal::currentUser()->id())
      ->execute();

    // Logged user votes.
    $this->drupalLogin($this->web_user);
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 2)));

    // Second anonymous user votes from same IP than the logged.
    $this->drupalLogout();
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 3)));

    // Second anonymous user cancels own vote.
    $this->drupalPostForm(NULL, array(), t('Cancel vote'));

    // Vote again to see the results, resulting in three votes again.
    $this->drupalPostForm('poll/' . $this->poll->id(), $edit, t('Vote'));
    $this->assertText(t('Total votes: @votes', array('@votes' => 3)));
  }

  /**
   * Tests switching between viewing the poll and the poll results.
   */
  public function testViewPollAndPollResultsAsAuthenticatedUser() {
    $this->poll->setResultVoteAllow(TRUE);
    $this->poll->save();

    // Login as user who may vote.
    $this->drupalLogin($this->web_user);

    // Go the poll form.
    $this->drupalGet('poll/' . $this->poll->id());

    // View the results.
    $this->drupalPostForm(NULL, [], 'View results');
    $this->assertSession()->pageTextContains('Total votes: 0');

    // Go back to the poll.
    // @todo button 'View poll' should appear.
    $this->drupalGet('poll/' . $this->poll->id());

    // And vote.
    $edit = [
      'choice' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Vote');
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');
  }

  /**
   * Tests switching between viewing the poll and the poll results.
   */
  public function testViewPollAndPollResultsAsAnonymousUser() {
    // Grant anonymous users permission to vote.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['cancel own vote', 'access polls']);
    $this->poll->setAnonymousVoteAllow(TRUE)
      ->setResultVoteAllow(TRUE)
      ->save();

    $this->drupalLogout();

    // Go the poll form.
    $this->drupalGet('poll/' . $this->poll->id());

    // View the results.
    $this->drupalPostForm(NULL, [], 'View results');
    $this->assertSession()->pageTextContains('Total votes: 0');

    // Go back to the poll.
    $this->drupalPostForm(NULL, [], 'View poll');

    // And vote.
    $edit = [
      'choice' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, 'Vote');
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->pageTextContains('Total votes: 1');
  }

}
