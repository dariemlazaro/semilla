<?php

namespace Drupal\simplenews\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simplenews\Entity\Subscriber;

/**
 * Add subscriptions for authenticated user or new subscriber.
 */
class SubscriptionsBlockForm extends SubscriptionsFormBase {

  /**
   * Form unique ID.
   *
   * @var string
   */
  protected $uniqueId;

  /**
   * A message to use as description for the block.
   *
   * @var string
   */
  protected $message;

  /**
   * The newsletters available to select from.
   *
   * @var string[]
   */
  protected $newsletterIds = [];

  /**
   * The default newsletters.
   *
   * @var string[]
   */
  protected $defaultNewsletterIds = [];

  /**
   * Whether to show "Manage existing" link.
   *
   * @var bool
   */
  protected $showManage = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty($this->uniqueId)) {
      throw new \Exception('Unique ID must be set with setUniqueId.');
    }
    return 'simplenews_subscriptions_block_' . $this->uniqueId;
  }

  /**
   * Setup unique ID.
   *
   * @param string $id
   *   Subscription block unique form ID.
   *
   * @return $this
   */
  public function setUniqueId($id) {
    $this->uniqueId = $id;
    return $this;
  }

  /**
   * Set message.
   *
   * @param string $message
   *   Message to use as description for the block.
   *
   * @return $this
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * Set the newsletters available to select from.
   *
   * @param string[] $newsletters
   *   Newsletter IDs available to select from.
   * @param string[] $defaults
   *   (optional) Newsletter IDs selected by default.
   *
   * @return $this
   */
  public function setNewsletterIds(array $newsletters, array $defaults = []) {
    $visible = array_keys(simplenews_newsletter_get_visible());
    // Exclude newsletters already subscribed.
    $subscribed = $this->entity->getSubscribedNewsletterIds();
    $this->newsletterIds = array_diff(array_intersect($newsletters, $visible), $subscribed);
    $this->defaultNewsletterIds = array_diff(array_intersect($defaults, $visible), $subscribed);
    return $this;
  }

  /**
   * Returns the newsletters available to select from.
   *
   * @return string[]
   *   The newsletter IDs available to select from, as an indexed array.
   */
  public function getNewsletterIds() {
    return $this->newsletterIds;
  }

  /**
   * Set whether to show "Manage existing" link.
   *
   * @param bool $show
   *   TRUE to show "Manage existing" link, FALSE to hide.
   *
   * @return $this
   */
  public function setShowManage($show) {
    $this->showManage = $show;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->getSubscriptionWidget($form_state)->setAvailableNewsletterIds($this->newsletterIds);

    if (!$form_state->getUserInput()) {
      // Set defaults.
      foreach ($this->defaultNewsletterIds as $newsletter_id) {
        $this->entity->subscribe($newsletter_id, SIMPLENEWS_SUBSCRIPTION_STATUS_SUBSCRIBED, 'website');
      }
    }

    $form = parent::form($form, $form_state);
    $form['subscriptions']['widget']['#title'] = $this->t('Manage your newsletter subscriptions');
    $form['subscriptions']['widget']['#description'] = $this->t('Select the newsletter(s) to which you want to subscribe.');
    $hidden_default_ids = array_diff($this->defaultNewsletterIds, $this->getNewsletterIds());
    $form['subscriptions']['widget']['#required'] = empty($hidden_default_ids);
    $form['subscriptions']['widget']['#access'] = !empty($this->newsletterIds);

    if (!$this->newsletterIds && !$this->defaultNewsletterIds) {
      $this->message = $this->t('You are already subscribed');
    }

    if ($this->message) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $this->message,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Subscribe');
    if (!$this->newsletterIds && !$this->defaultNewsletterIds) {
      $actions['submit']['#attributes']['disabled'] = TRUE;
    }

    if ($this->showManage) {
      $user = \Drupal::currentUser();
      $link = $user->isAuthenticated() ? Url::fromRoute('simplenews.newsletter_subscriptions_user', ['user' => $user->id()]) : Url::fromRoute('simplenews.newsletter_validate');
      $actions['manage'] = [
        '#title' => $this->t('Manage existing'),
        '#type' => 'link',
        '#url' => $link,
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mail = $form_state->getValue(['mail', 0, 'value']);
    if ($this->entity->isNew() && $subscriber = Subscriber::loadByMail($mail)) {
      $this->setEntity($subscriber);
    }

    parent::validateForm($form, $form_state);

    $mail = $form_state->getValue(['mail', 0, 'value']);
    // Cannot subscribe blocked users.
    if (($user = user_load_by_mail($mail)) && $user->isBlocked()) {
      $message = $this->t('The email address %mail belongs to a blocked user.', ['%mail' => $mail]);
      $form_state->setErrorByName('mail', $message);
    }
  }

  /**
   * Submit callback that subscribes to selected newsletters.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitExtra(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\simplenews\Subscription\SubscriptionManagerInterface $subscription_manager */
    $subscription_manager = \Drupal::service('simplenews.subscription_manager');

    // Subscribe the selected newsletters and any defaults that are hidden.
    $selected_ids = $this->extractNewsletterIds($form_state, TRUE);
    $hidden_default_ids = array_diff($this->defaultNewsletterIds, $this->getNewsletterIds());

    foreach (array_unique(array_merge($selected_ids, $hidden_default_ids)) as $newsletter_id) {
      $subscription_manager->subscribe($this->entity->getMail(), $newsletter_id, NULL, 'website');
    }
    $sent = $subscription_manager->sendConfirmations();
    $this->messenger()->addMessage($this->getSubmitMessage($form_state, $sent));
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitMessage(FormStateInterface $form_state, $confirm) {
    if ($confirm) {
      return $this->t('You will receive a confirmation e-mail shortly containing further instructions on how to complete your subscription.');
    }
    return $this->t('You have been subscribed.');
  }

}
