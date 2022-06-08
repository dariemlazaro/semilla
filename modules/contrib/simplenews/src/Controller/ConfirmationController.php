<?php

namespace Drupal\simplenews\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\simplenews\Entity\Subscriber;
use Drupal\simplenews\Entity\Newsletter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simplenews\Subscription\SubscriptionManagerInterface;

/**
 * Returns responses for confirmation and subscriber routes.
 */
class ConfirmationController extends ControllerBase {

  /**
   * The subscription manager.
   *
   * @var \Drupal\simplenews\Subscription\SubscriptionManagerInterface
   */
  protected $subscriptionManager;

  /**
   * Constructs a \Drupal\simplenews\Controller\ConfirmationController object.
   *
   * @param \Drupal\simplenews\Subscription\SubscriptionManagerInterface $subscription_manager
   *   The subscription manager service.
   */
  public function __construct(SubscriptionManagerInterface $subscription_manager) {
    $this->subscriptionManager = $subscription_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simplenews.subscription_manager')
    );
  }

  /**
   * Menu callback: confirm a combined confirmation request.
   *
   * This function is called by clicking the confirm link in the confirmation
   * email. It handles both subscription addition and subscription removal.
   *
   * @param int $snid
   *   The subscriber id.
   * @param int $timestamp
   *   The timestamp of the request.
   * @param bool $hash
   *   The confirmation hash.
   * @param bool $immediate
   *   Perform the action immediately if TRUE.
   *
   * @see simplenews_confirm_add_form()
   * @see simplenews_confirm_removal_form()
   */
  public function confirmCombined($snid, $timestamp, $hash, $immediate = FALSE) {
    $config = $this->config('simplenews.settings');

    // Prevent search engines from indexing this page.
    $html_head = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex',
        ],
      ],
      'simplenews-noindex',
    ];

    $subscriber = Subscriber::load($snid);

    // Redirect and display message if no changes are available.
    if ($subscriber && !$subscriber->getChanges()) {
      $this->messenger()->addMessage($this->t('All changes to your subscriptions where already applied. No changes made.'));
      return $this->redirect('<front>');
    }

    if ($subscriber && $hash == simplenews_generate_hash($subscriber->getMail(), 'combined' . serialize($subscriber->getChanges()), $timestamp)) {
      // If the hash is valid but timestamp is too old, display form to request
      // a new hash.
      if ($timestamp < REQUEST_TIME - $config->get('hash_expiration')) {
        $context = [
          'simplenews_subscriber' => $subscriber,
        ];
        $build = $this->formBuilder()->getForm('\Drupal\simplenews\Form\RequestHashForm', 'subscribe_combined', $context);
        $build['#attached']['html_head'][] = $html_head;
        return $build;
      }
      // When not called with immediate parameter the user will be directed to
      // the (un)subscribe confirmation page.
      if (!$immediate) {
        $build = $this->formBuilder()->getForm('\Drupal\simplenews\Form\ConfirmMultiForm', $subscriber);
        $build['#attached']['html_head'][] = $html_head;
        return $build;
      }
      else {

        // Redirect and display message if no changes are available.
        foreach ($subscriber->getChanges() as $newsletter_id => $action) {
          if ($action == 'subscribe') {
            $this->subscriptionManager->subscribe($subscriber->getMail(), $newsletter_id, FALSE, 'website');
          }
          elseif ($action == 'unsubscribe') {
            $this->subscriptionManager->unsubscribe($subscriber->getMail(), $newsletter_id, FALSE, 'website');
          }
        }

        // Clear changes.
        $subscriber->setChanges([]);
        $subscriber->save();

        $this->messenger()->addMessage($this->t('Subscription changes confirmed for %user.', ['%user' => $subscriber->getMail()]));
        return $this->redirect('<front>');
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Menu callback: confirm the user's (un)subscription request.
   *
   * This function is called by clicking the confirm link in the confirmation
   * email or the unsubscribe link in the footer of the newsletter. It handles
   * both subscription addition and subscription removal.
   *
   * Calling URLs are:
   * newsletter/confirm/add
   * newsletter/confirm/add/$HASH
   * newsletter/confirm/remove
   * newsletter/confirm/remove/$HASH
   *
   * @see simplenews_confirm_add_form()
   * @see simplenews_confirm_removal_form()
   */

  /**
   * Menu callback: confirm the user's (un)subscription request.
   *
   * This function is called by clicking the confirm link in the confirmation
   * email or the unsubscribe link in the footer of the newsletter. It handles
   * both subscription addition and subscription removal.
   *
   * @param string $action
   *   Either add or remove.
   * @param int $snid
   *   The subscriber id.
   * @param int $newsletter_id
   *   The newsletter id.
   * @param int $timestamp
   *   The timestamp of the request.
   * @param string $hash
   *   The confirmation hash.
   * @param bool $immediate
   *   Perform the action immediately if TRUE.
   *
   * @see simplenews_confirm_add_form()
   * @see simplenews_confirm_removal_form()
   */
  public function confirmSubscription($action, $snid, $newsletter_id, $timestamp, $hash, $immediate = FALSE) {
    $config = $this->config('simplenews.settings');

    // Prevent search engines from indexing this page.
    $html_head = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex',
        ],
      ],
      'simplenews-noindex',
    ];

    $subscriber = Subscriber::load($snid);
    if ($subscriber && $hash == simplenews_generate_hash($subscriber->getMail(), $action, $timestamp)) {
      $newsletter = Newsletter::load($newsletter_id);

      // If the hash is valid but timestamp is too old, display form to request
      // a new hash.
      if ($timestamp < REQUEST_TIME - $config->get('hash_expiration')) {
        $context = [
          'simplenews_subscriber' => $subscriber,
          'newsletter' => $newsletter,
        ];
        $key = $action == 'add' ? 'subscribe_combined' : 'validate';
        $build = $this->formBuilder()->getForm('\Drupal\simplenews\Form\RequestHashForm', $key, $context);
        $build['#attached']['html_head'][] = $html_head;
        return $build;
      }
      // When called with additional arguments the user will be directed to the
      // (un)subscribe confirmation page. The additional arguments will be
      // passed on to the confirmation page.
      if (!$immediate) {
        if ($action == 'remove') {
          $build = $this->formBuilder()->getForm('\Drupal\simplenews\Form\ConfirmRemovalForm', $subscriber->getMail(), $newsletter);
          $build['#attached']['html_head'][] = $html_head;
          return $build;
        }
        elseif ($action == 'add') {
          $build = $this->formBuilder()->getForm('\Drupal\simplenews\Form\ConfirmAddForm', $subscriber->getMail(), $newsletter);
          $build['#attached']['html_head'][] = $html_head;
          return $build;
        }
      }
      else {

        if ($action == 'remove') {
          $this->subscriptionManager->unsubscribe($subscriber->getMail(), $newsletter_id, FALSE, 'website');
          if ($path = $config->get('subscription.confirm_unsubscribe_page')) {
            return $this->redirect(Url::fromUri("internal:$path")->getRouteName());
          }
          $this->messenger()->addMessage($this->t('%user was unsubscribed from the %newsletter mailing list.', ['%user' => $subscriber->getMail(), '%newsletter' => $newsletter->name]));
          return $this->redirect('<front>');
        }
        elseif ($action == 'add') {
          $this->subscriptionManager->subscribe($subscriber->getMail(), $newsletter_id, FALSE, 'website');
          if ($path = $config->get('subscription.confirm_subscribe_page')) {
            return $this->redirect(Url::fromUri("internal:$path")->getRouteName());
          }
          $this->messenger()->addMessage($this->t('%user was added to the %newsletter mailing list.', ['%user' => $subscriber->getMail(), '%newsletter' => $newsletter->name]));
          return $this->redirect('<front>');
        }
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Redirects subscribers to the appropriate page.
   *
   * Redirect to the 'Newsletters' tab for authenticated users or the 'Access
   * your subscriptions' page otherwise.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the correct page.
   */
  public function subscriptionsPage() {
    $user = $this->currentUser();

    if ($user->isAuthenticated()) {
      return $this->redirect('simplenews.newsletter_subscriptions_user', ['user' => $user->id()]);
    }
    return $this->redirect('simplenews.newsletter_validate');
  }

}
