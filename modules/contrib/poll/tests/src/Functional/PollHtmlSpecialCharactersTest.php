<?php

namespace Drupal\Tests\poll\Functional;

/**
 * Check that HTML special characters are displayed correctly.
 *
 * @group poll
 */
class PollHtmlSpecialCharactersTest extends PollTestBase {

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->special_choice = '> old & spice <';
    $this->page_title = 'Salt & pepper > here';

    $this->poll
      ->setQuestion($this->page_title)
      ->setAnonymousVoteAllow(TRUE)
      ->save();
  }

  /**
   * Test that HTML characters in the title are displayed correctly.
   */
  public function testPollQuestion() {
    // Verify user can view poll.
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertResponse(200);

    // Verify the page title.
    $result = $this->xpath("//div[contains(concat(' ', @class, ' '), ' block-page-title-block ')]/h1");
    $this->assertEqual($this->page_title, $result[0]->getText(), 'HTML entities displayed correctly in page title.');

    // Verify the poll title is escaped correctly in the poll results.
    $this->drupalPostForm('poll/' . $this->poll->id(), ['choice' => 1], t('Vote'));
    $this->assertText('Your vote has been recorded.');
    $this->drupalGet('polls');
    $this->assertSession()->elementTextContains('css', 'h3.poll-question', $this->page_title);
  }

  /**
   * Test that HTML characters in choices are displayed correctly.
   */
  public function testPollChoice() {
    $poll = $this->poll;
    // Update the first choice.
    $poll->choice[0]->entity->setChoice($this->special_choice);
    $poll->choice[0]->entity->save();

    // View the updated poll.
    $this->drupalGet('poll/' . $this->poll->id());
    $this->assertResponse(200);

    // Verify the updated choice.
    $result = $this->xpath("//div[@id='edit-choice']/div[1]/label/text()");
    $this->assertEqual($this->special_choice, $result[0]->getText(), 'HTML entities displayed correctly in choice option.');

    // Vote.
    $this->drupalPostForm('poll/' . $this->poll->id(), ['choice' => 1], t('Vote'));
    $this->assertText('Your vote has been recorded.');
    $this->assertText('Total votes: 1');

    // Verify the results page.
    $result = $this->xpath('//*[@id="poll-view-form-1"]/div/dl/dt[1]/text()');
    $this->assertEqual($this->special_choice, $result[0]->getText(), 'HTML entities displayed correctly in vote results.');
  }
}
