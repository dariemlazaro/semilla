<?php

namespace Drupal\simple_sitemap_engines\Form;

use Drupal\simple_sitemap\Form\FormHelper as BaseFormHelper;

/**
 * Slightly altered version of the Simple XML Sitemap form helper.
 */
class FormHelper extends BaseFormHelper {

  /**
   * {@inheritdoc}
   */
  protected static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
    'delete',
  ];

  /**
   * {@inheritdoc}
   */
  protected function userAccess(): bool {
    return $this->currentUser->hasPermission('administer sitemap settings') || $this->currentUser->hasPermission('index entity on save');
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntitySettings(array &$form_fragment): BaseFormHelper {
    $form_fragment['index_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->entityCategory === 'bundle'
      ? t('Notify IndexNow search engines of changes <em>by default</em>')
      : t('Notify IndexNow search engines of changes <em>now</em>'),
      '#description' => $this->entityCategory === 'bundle'
      ? t('Send change notice to IndexNow compatible search engines right after submitting entity forms of this type.<br/>Changes include creating, deleting and updating of an entity. This setting can be overridden on the entity form.')
      : t('Send change notice to IndexNow compatible search engines right after submitting this form.'),
      '#default_value' => $this->bundleName
      ? (int) \Drupal::config("simple_sitemap_engines.bundle_settings.$this->entityTypeId.$this->bundleName")->get('index_now')
      : 0,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function addSubmitHandler(array &$form, callable $callback) {
    if (isset($form['actions']['submit']['#submit'])) {
      foreach (array_keys($form['actions']) as $action) {
        if ($action !== 'preview'
          && isset($form['actions'][$action]['#type'])
          && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'] = array_merge([$callback], $form['actions'][$action]['#submit']);
        }
      }
    }
    // Fix for account page rendering other submit handlers not usable.
    else {
      $form['#submit'][] = $callback;
    }
  }

}
