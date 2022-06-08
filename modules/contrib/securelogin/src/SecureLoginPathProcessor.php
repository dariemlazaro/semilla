<?php

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Secure login path processor.
 *
 * This path processor applies a configured secure base URL. It is useful for
 * sites that have multiple insecure base URLs and an SSL certificate valid only
 * for one secure base URL.
 */
class SecureLoginPathProcessor implements OutboundPathProcessorInterface {

  /**
   * The configured secure login base URL.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Constructs secure login path processor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->baseUrl = $config_factory->get('securelogin.settings')->get('base_url');
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (!empty($options['https']) && $this->baseUrl) {
      $options['absolute'] = TRUE;
      $options['base_url'] = $this->baseUrl;
    }
    return $path;
  }

}
