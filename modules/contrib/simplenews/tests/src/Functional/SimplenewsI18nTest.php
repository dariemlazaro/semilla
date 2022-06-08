<?php

namespace Drupal\Tests\simplenews\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\simplenews\Entity\Newsletter;

/**
 * Translation of newsletters and issues.
 *
 * @group simplenews
 */
class SimplenewsI18nTest extends SimplenewsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'locale', 'config_translation', 'content_translation',
  ];

  /**
   * Administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Default language.
   *
   * @var string
   */
  protected $defaultLanguage;

  /**
   * Secondary language.
   *
   * @var string
   */
  protected $secondaryLanguage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access', 'administer nodes', 'administer languages', 'administer content types', 'access administration pages', 'administer filters', 'translate interface', 'subscribe to newsletters', 'administer site configuration', 'translate any entity', 'administer content translation', 'administer simplenews subscriptions', 'send newsletter', 'create content translations',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->setUpLanguages();
  }

  /**
   * Set up configuration for multiple languages.
   */
  protected function setUpLanguages() {

    // Add languages.
    $this->defaultLanguage = 'en';
    $this->secondaryLanguage = 'es';
    $this->addLanguage($this->secondaryLanguage);

    // Display the language widget.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'simplenews_issue');
    $config->setLanguageAlterable(TRUE);
    $config->save();

    // Make Simplenews issue translatable.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'simplenews_issue', TRUE);
    drupal_static_reset();
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();

    // Make Simplenews issue body translatable.
    $field = FieldConfig::loadByName('node', 'simplenews_issue', 'body');
    $field->setTranslatable(TRUE);
    $field->save();
    $this->rebuildContainer();
  }

  /**
   * Install a the specified language if it has not been already.
   *
   * Otherwise make sure that the language is enabled.
   *
   * Copied from Drupali18nTestCase::addLanguage().
   *
   * @param string $language_code
   *   The language code the check.
   */
  protected function addLanguage($language_code) {
    $language = ConfigurableLanguage::createFromLangcode($language_code);
    $language->save();
  }

  /**
   * Test newsletter issue translations.
   */
  public function testNewsletterIssueTranslation() {
    // Sign up three users, one in english and two in spanish.
    $english_mail = $this->randomEmail();
    $spanish_mail = $this->randomEmail();
    $spanish_mail2 = $this->randomEmail();
    $newsletter_id = $this->getRandomNewsletter();

    /** @var \Drupal\simplenews\Subscription\SubscriptionManagerInterface $subscription_manager */
    $subscription_manager = \Drupal::service('simplenews.subscription_manager');

    $subscription_manager->subscribe($english_mail, $newsletter_id, FALSE, 'english', 'en');
    $subscription_manager->subscribe($spanish_mail, $newsletter_id, FALSE, 'spanish', 'es');
    $subscription_manager->subscribe($spanish_mail2, $newsletter_id, FALSE, 'spanish', 'es');

    // Enable translation for newsletters.
    $edit = [
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalGet('admin/structure/types/manage/simplenews_issue');
    $this->submitForm($edit, 'Save content type');

    // Create a Newsletter including a translation.
    $newsletter_id = $this->getRandomNewsletter();
    $english = [
      'title[0][value]' => $this->randomMachineName(),
      'simplenews_issue[target_id]' => $newsletter_id,
      'body[0][value]' => 'Link to node: [node:url]',
    ];
    $this->drupalGet('node/add/simplenews_issue');
    $this->submitForm($english, 'Save');
    $this->assertEqual(1, preg_match('|node/(\d+)$|', $this->getUrl(), $matches), 'Node created');
    $node = Node::load($matches[1]);

    $this->clickLink(t('Translate'));
    $this->clickLink(t('Add'));
    $spanish = [
      'title[0][value]' => $this->randomMachineName(),
      'body[0][value]' => 'Link to node: [node:url] ES',
    ];
    $this->submitForm($spanish, 'Save (this translation)');

    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    $translation = $node->getTranslation($this->secondaryLanguage);

    // Send newsletter.
    $this->clickLink(t('Newsletter'));
    $this->submitForm([], t('Send now'));
    $this->cronRun();

    $this->assertCount(3, $this->getMails());

    $newsletter = Newsletter::load($newsletter_id);
    foreach ($this->getMails() as $mail) {

      if ($mail['to'] == $english_mail) {
        $this->assertEqual('en', $mail['langcode']);
        $this->assertEqual('[' . $newsletter->label() . '] ' . $node->getTitle(), $mail['subject']);
        $node_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
        $title = $node->getTitle();
      }
      elseif ($mail['to'] == $spanish_mail || $mail['to'] == $spanish_mail2) {
        $this->assertEqual('es', $mail['langcode']);
        // @todo: Verify newsletter translation once supported again.
        $this->assertEqual('[' . $newsletter->name . '] ' . $translation->label(), $mail['subject']);
        $node_url = $translation->toUrl('canonical', ['absolute' => TRUE, 'language' => $translation->language()])->toString();
        $title = $translation->getTitle();
      }
      else {
        $this->fail(t('Mail not sent to expected recipient'));
      }

      // Verify that the link is in the correct language.
      $this->assertStringContainsString($node_url, $mail['body']);
      // The <h1> tag is converted to uppercase characters in D8.
      // @todo Remove "IgnoringCase" after D8 EOL
      $this->assertStringContainsStringIgnoringCase($title, $mail['body']);
    }

    // Verify sent subscriber count.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    $node = Node::load($node->id());
    $translation = $node->getTranslation($this->secondaryLanguage);
    $this->assertEqual(3, $node->simplenews_issue->sent_count, 'subscriber count is correct');
    $this->drupalGet('/admin/content/simplenews');
    $this->assertSession()->responseContains('<span title="Newsletter issue sent to 3 subscribers, 0 errors.">3/3</span>');

    // Make sure the language of a node can be changed.
    $english = [
      'title[0][value]' => $this->randomMachineName(),
      'langcode[0][value]' => 'en',
      'body[0][value]' => 'Link to node: [node:url]',
    ];
    $this->drupalGet('node/add/simplenews_issue');
    $this->submitForm($english, 'Save');
    $this->clickLink(t('Edit'));
    $edit = [
      'langcode[0][value]' => 'es',
    ];
    $this->submitForm($edit, 'Save');
  }

}
