<?php

namespace Drupal\searchindex_wipe\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SearchIndexWipeConfirm.
 *
 * @package
 * Drupal\searchindex_wipe\Form
 */
class SearchIndexWipeConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'searchindex_wipe_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clearing of Search related indexes?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_page.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form['confirm']) {
      searchindex_wipe_truncate_table();
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
