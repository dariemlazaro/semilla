<?php

namespace Drupal\Tests\poll\Functional;

/**
 * Tests creating a poll.
 *
 * @group poll
 */
class PollCreateTest extends PollTestBase {

  /**
   * Tests creating and editing a poll.
   */
  public function testPollCreate() {

    $poll = $this->poll;

    // Check we loaded the right poll.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('poll/' . $poll->id() . '/edit');
    $this->assertText($poll->label());

    // Verify applying condition for non-active polls.
    $this->drupalGet('admin/content/poll', ['query' => ['status' => '2']]);
    $this->assertNoText($poll->label());

    // Verify poll appears on 'poll' page.
    $this->drupalGet('admin/content/poll');
    $this->assertText($poll->label());
    $this->assertText('Y');

    // Click on the poll question to go to poll page.
    $this->clickLink($poll->label());

    // Alter the question and ensure it gets saved correctly.
    $new_question = $this->randomMachineName();
    $poll->setQuestion($new_question);
    $poll->save();

    // Check the new question has taken effect.
    $this->drupalGet('poll/' . $poll->id() . '/edit');
    $this->assertText($new_question);

    // Now add a new option to make sure that when we update the poll, the
    // option is displayed.
    $vote_choice = $this->randomMachineName();
    $poll->choice[0]->entity->setChoice($vote_choice);
    $poll->choice[0]->entity->save();

    // Check the new choice has taken effect.
    $this->drupalGet('poll/' . $poll->id() . '/edit');
    $this->assertFieldByXPath("//input[@name='choice[0][choice]']", $vote_choice, 'Choice successfully changed.');

  }

  /**
   * Tests creating, editing, and closing a poll.
   */
  function testPollClose() {

    $poll = $this->poll;
    $poll->close();
    $poll->save();

    $this->drupalLogin($this->web_user);

    // Poll create disallowed.
    $this->drupalGet('poll/add');
    $this->assertResponse(403);

    // Get a poll.
    $this->drupalGet('poll/' . $poll->id());

    // Verify 'Vote' button no longer appears.
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertTrue(empty($elements), "Vote button doesn't appear.");

    // Verify 'View Poll' button no longer appears.
    $elements = $this->xpath('value="View poll"');
    $this->assertTrue(empty($elements), "View poll button doesn't appear.");

    // Edit the poll and re-activate.
    $poll->open();
    $poll->save();
    $this->drupalGet('poll/' . $poll->id());

    // Verify 'Vote' button no appears.
    $elements = $this->xpath('//input[@value="Vote"]');
    $this->assertFalse(empty($elements), "Vote button appears.");

    // Check to see if the vote was recorded and that the user may cancel their vote.
    $edit = array('choice' => 1);
    $this->drupalPostForm(NULL, $edit, t('Vote'));
    $this->assertText('Your vote has been recorded.');
    $elements = $this->xpath('//input[@value="Cancel vote"]');
    $this->assertTrue(isset($elements[0]), "'Cancel vote' button appears.");

    // Verify 'Cancel your vote' button no longer appears after poll is closed.
    $poll->close();
    $poll->save();
    $this->drupalGet('poll/' . $poll->id());
    $elements = $this->xpath('//input[@value="Cancel your vote"]');
    $this->assertTrue(empty($elements), "'Cancel your vote' button no longer appears.");
  }

  /**
   * Poll create with restricted user.
   */
  function testwithRestrictedUser() {
    $admin_poll = $this->poll;
    // Create and login restricted user.
    $account = $this->drupalCreateUser([
      'create polls',
    ]);
    $this->drupalLogin($account);
    // Create poll allowed.
    $this->drupalGet('poll/add');
    $this->assertResponse(200);
    $this->assertNoFieldByName('uid[0][target_id]', NULL);
    // create poll and test edit
    $own_poll = $this->pollCreate(7, $account);
    $this->drupalGet('poll/' . $admin_poll->id() . '/edit');
    $this->assertResponse(403);
    $this->drupalGet('poll/' . $own_poll->id() . '/edit');
    $this->assertResponse(403);
    // test another user with "edit own poll" permission
    $account = $this->drupalCreateUser([
      'create polls',
      'edit own polls',
    ]);
    $this->drupalLogin($account);
    $own_poll = $this->pollCreate(7, $account);
    $this->drupalGet('poll/' . $admin_poll->id() . '/edit');
    $this->assertResponse(403);
    $this->drupalGet('poll/' . $own_poll->id() . '/edit');
    $this->assertResponse(200);
    $this->assertNoFieldByName('uid[0][target_id]', NULL);
  }
}
