<?php

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for insecure password reset login requests and redirects to HTTPS.
 */
class SecureLoginRequestSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SecureLoginRequestSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match) {
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
  }

  /**
   * Redirects insecure password reset attempts to the secure site.
   */
  public function onRequest($event) {
    $request = $event->getRequest();
    if ($request->isSecure()) {
      return;
    }
    if ($this->routeMatch->getRouteName() !== 'user.reset.login') {
      return;
    }
    $config = $this->configFactory->get('securelogin.settings');
    if (!$config->get('form_user_pass_reset')) {
      return;
    }
    $url = Url::fromRouteMatch($this->routeMatch)
      ->setAbsolute()
      ->setOption('external', FALSE)
      ->setOption('https', TRUE)
      ->setOption('query', $request->query->all())
      ->toString();
    $status = $request->isMethodCacheable() ? TrustedRedirectResponse::HTTP_MOVED_PERMANENTLY : TrustedRedirectResponse::HTTP_PERMANENTLY_REDIRECT;
    $event->setResponse(new TrustedRedirectResponse($url, $status));
    // Redirect URL has destination so consider this the final destination.
    $request->query->set('destination', '');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 2];
    return $events;
  }

}
