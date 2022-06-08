<?php

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines the secure login service.
 */
class SecureLoginManager implements TrustedCallbackInterface {

  use DependencySerializationTrait;

  /**
   * Form action placeholder for the form builder.
   */
  const FORM_PLACEHOLDER = 'form_action_p_pvdeGsVG5zNF_XLGPTvYSKCf43t8qZYSwcfZl2uzM';

  /**
   * Form action placeholder for the user login block.
   */
  const USER_PLACEHOLDER = 'form_action_p_4r8ITd22yaUvXM6SzwrSe9rnQWe48hz9k1Sxto3pBvE';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs the secure login service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack, FormBuilderInterface $form_builder = NULL) {
    $this->configFactory = $config_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
    $this->formBuilder = $form_builder;
  }

  /**
   * Rewrites the form action to use the secure base URL.
   */
  public function secureForm(&$form) {
    // Rebuild this form on based on the actual URL.
    $form['#cache']['contexts'][] = 'securelogin';
    // Flag form as secure for theming purposes.
    $form['#https'] = TRUE;
    // Core secures the form action when #https is enabled, but doesn't use our
    // configured base URL, so undo it, and re-do it if necessary.
    $form['#after_build'][] = [$this, 'afterBuild'];
    if ($this->requestStack->getCurrentRequest()->isSecure()) {
      return;
    }
    // Redirect to secure page, if enabled.
    if ($this->configFactory->get('securelogin.settings')->get('secure_forms')) {
      // Disable caching, as this form must be rebuilt to set the redirect.
      $form['#cache']['max-age'] = 0;
      $this->secureRedirect();
    }
    $form['#action'] = $this->secureUrl($form['#action']);
    if (isset($form['#attached']['placeholders'][self::FORM_PLACEHOLDER])) {
      $form['#attached']['placeholders'][self::FORM_PLACEHOLDER]['#lazy_builder'] = [
        'securelogin.manager:renderPlaceholderFormAction',
        [self::FORM_PLACEHOLDER],
      ];
    }
    if (isset($form['#attached']['placeholders'][self::USER_PLACEHOLDER])) {
      $form['#attached']['placeholders'][self::USER_PLACEHOLDER]['#lazy_builder'] = [
        'securelogin.manager:renderPlaceholderFormAction',
        [self::USER_PLACEHOLDER],
      ];
    }
  }

  /**
   * Reimplement core form securing to use configured base URL.
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    global $base_root;
    $prefix = str_replace('http://', 'https://', $base_root);
    if (!empty($form['#https']) && isset($form['#action']) && strpos($form['#action'], $prefix) === 0) {
      // Undo core's forcing the URL to HTTPS.
      $form['#action'] = substr($form['#action'], strlen($prefix));
      // Resecure form action if we're on HTTP site.
      if (!$this->requestStack->getCurrentRequest()->isSecure()) {
        $form['#action'] = $this->secureUrl($form['#action']);
      }
    }
    return $form;
  }

  /**
   * Redirects a request to the same path on the secure base URL.
   */
  public function secureRedirect() {
    // Do not redirect from HTTPS requests.
    if ($this->requestStack->getCurrentRequest()->isSecure()) {
      return;
    }
    $status = $this->requestStack->getCurrentRequest()->isMethodCacheable() ? TrustedRedirectResponse::HTTP_MOVED_PERMANENTLY : TrustedRedirectResponse::HTTP_PERMANENTLY_REDIRECT;
    // Build the redirect URL from the master request.
    $method = method_exists($this->requestStack, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest';
    $request = $this->requestStack->$method();
    // Request may be a 404 so handle as unrouted URI.
    $url = Url::fromUri("internal:{$request->getPathInfo()}");
    $url->setOption('absolute', TRUE)
      ->setOption('external', FALSE)
      ->setOption('https', TRUE)
      ->setOption('query', $request->query->all());
    // Create listener to set the redirect response.
    $listener = function ($event) use ($url, $status) {
      $response = new TrustedRedirectResponse($url->toString(), $status);
      // Page cache has a fatal error if cached response has no Expires header.
      $response->setExpires(\DateTime::createFromFormat('j-M-Y H:i:s T', '19-Nov-1978 05:00:00 UTC'));
      // Add cache context for this redirect.
      $response->addCacheableDependency(new SecureLoginCacheableDependency());
      $event->setResponse($response);
      // Redirect URL has destination so consider this the final destination.
      $event->getRequest()->query->set('destination', '');
    };
    // Add listener to response event at high priority.
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener, 222);
  }

  /**
   * Rewrites a URL to use the secure base URL.
   */
  public function secureUrl($url) {
    global $base_path, $base_secure_url;
    // Set the form action to use secure base URL in place of base path.
    if (strpos($url, $base_path) === 0) {
      $base_url = $this->configFactory->get('securelogin.settings')->get('base_url') ?: $base_secure_url;
      return substr_replace($url, $base_url, 0, strlen($base_path) - 1);
    }
    // If URL is absolute, forcibly rewrite to HTTPS.
    return preg_replace('#^http://#i', 'https://', $url);
  }

  /**
   * Lazy builder callback; renders a form action URL including destination.
   *
   * @return array
   *   A renderable array representing the form action.
   *
   * @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
   */
  public function renderPlaceholderFormAction($placeholder = NULL) {
    // This method is not defined in the interface, so check that it exists.
    if ($placeholder === self::FORM_PLACEHOLDER && method_exists($this->formBuilder, 'renderPlaceholderFormAction')) {
      $action = $this->formBuilder->renderPlaceholderFormAction();
    }
    else {
      $action = UserLoginBlock::renderPlaceholderFormAction();
    }
    $action['#markup'] = $this->secureUrl($action['#markup']);
    return $action;
  }

  /**
   * Returns list of trusted callbacks.
   */
  public static function trustedCallbacks() {
    return ['renderPlaceholderFormAction', 'userLoginBlockPreRender'];
  }

  /**
   * Pre-render callback to re-secure the user login block.
   */
  public function userLoginBlockPreRender($build) {
    if (!empty($build['content']['user_login_form']['#https'])) {
      $this->secureForm($build['content']['user_login_form']);
    }
    return $build;
  }

}
