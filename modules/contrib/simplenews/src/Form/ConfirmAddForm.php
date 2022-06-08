<?php

namespace Drupal\simplenews\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simplenews\NewsletterInterface;

/**
 * Implements a add confirmation form for simplenews subscriptions.
 */
class ConfirmAddForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm subscription');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Subscribe');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('You can always unsubscribe later.');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplenews_confirm_add';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simplenews.newsletter_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mail = '', NewsletterInterface $newsletter = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['question'] = [
      '#markup' => '<p>' . $this->t('Are you sure you want to add %user to the %newsletter mailing list?', ['%user' => simplenews_mask_mail($mail), '%newsletter' => $newsletter->name]) . "<p>\n",
    ];
    $form['mail'] = [
      '#type' => 'value',
      '#value' => $mail,
    ];
    $form['newsletter'] = [
      '#type' => 'value',
      '#value' => $newsletter,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('simplenews.subscription_manager')->subscribe($form_state->getValue('mail'), $form_state->getValue('newsletter')->id(), FALSE, 'website');

    $config = \Drupal::config('simplenews.settings');
    if ($path = $config->get('subscription.confirm_subscribe_page')) {
      $form_state->setRedirectUrl(Url::fromUri("internal:$path"));
    }
    else {
      $this->messenger()->addMessage($this->t('%user was added to the %newsletter mailing list.', ['%user' => $form_state->getValue('mail'), '%newsletter' => $form_state->getValue('newsletter')->name]));
      $form_state->setRedirect('<front>');
    }
  }

}
