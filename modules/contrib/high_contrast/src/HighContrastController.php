<?php

namespace Drupal\high_contrast;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This class handles the enabling and disabling of high contrast mode.
 *
 * @todo This feels somewhat hackish... There is probably a proper core implementation for this.
 */
class HighContrastController extends ControllerBase {

  use HighContrastTrait;

  /**
   * Holds the redirect path for after enabling high contrast.
   *
   * @var string
   */
  protected $redirectDestination;

  /**
   * Fetch and store the redirect path.
   */
  public function __construct() {
    $this->redirectDestination = \Drupal::request()->query->get('destination');
  }

  /**
   * Enable high contrast.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse object.
   */
  public function enable() {
    $this->enableHighContrast();
    return $this->goBack();
  }

  /**
   * Disable high contrast.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse object.
   */
  public function disable() {
    $this->disableHighContrast();
    return $this->goBack();
  }

  /**
   * Perform the redirect to the set path.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse object.
   */
  protected function goBack() {
    return new RedirectResponse($this->redirectDestination);
  }

}
