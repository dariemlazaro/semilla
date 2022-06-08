<?php

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for login to the 403 page and redirects to destination.
 */
class SecureLoginResponseSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new SecureLoginResponseSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CurrentPathStack $current_path, RouteMatchInterface $route_match, AccountInterface $current_user, RedirectDestinationInterface $redirect_destination) {
    $this->configFactory = $config_factory;
    $this->currentPath = $current_path;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * Redirects login attempts on already-logged-in session to the destination.
   *
   * @todo Should accept a ResponseEvent.
   */
  public function onRespond($event) {
    // Return early in most cases.
    if ($event->getRequest()->getMethod() !== 'POST') {
      return;
    }
    if (!$this->currentUser->isAuthenticated()) {
      return;
    }
    $method = method_exists($event, 'isMainRequest') ? 'isMainRequest' : 'isMasterRequest';
    if (!$event->$method()) {
      return;
    }
    if (!$event->getRequest()->query->has('destination')) {
      return;
    }
    if ($event->getResponse() instanceof RedirectResponse) {
      return;
    }
    // @todo Find a better way to figure out if we landed on the 403/404 page.
    $page_403 = $this->configFactory->get('system.site')->get('page.403');
    $page_404 = $this->configFactory->get('system.site')->get('page.404');
    $path = $this->currentPath->getPath();
    $route = $this->routeMatch->getRouteName();
    if ($route == 'system.403' || ($page_403 && $path == $page_403) || $route == 'system.404' || ($page_404 && $path == $page_404)) {
      // RedirectResponseSubscriber will convert to absolute URL for us.
      $event->setResponse(new RedirectResponse($this->redirectDestination->get(), RedirectResponse::HTTP_SEE_OTHER));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', 2];
    return $events;
  }

}
