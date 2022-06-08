<?php

namespace Drupal\printable\LinkExtractor;

use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;
use wa72\htmlpagedom\HtmlPageCrawler;

/**
 * Link extractor.
 */
class InlineLinkExtractor implements LinkExtractorInterface {

  /**
   * The DomCrawler object.
   *
   * @var \Wa72\HtmlPageDom\HtmlPageCrawler
   */
  protected $crawler;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * The alias manager service.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Constructs a new InlineLinkExtractor object.
   */
  public function __construct(HtmlPageCrawler $crawler, MetadataBubblingUrlGenerator $urlGenerator, AliasManager $aliasManager) {
    $this->crawler = $crawler;
    $this->urlGenerator = $urlGenerator;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($string) {
    $this->crawler->addContent($string);

    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      $href = $anchor->attr('href');
      if ($href) {
        $url = $this->urlFromHref($href);
        $anchor->append(' (' . $url->toString() . ')');
      }
    });

    return (string) $this->crawler;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAttribute($content, $attr) {
    $this->crawler->addContent($content);
    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      $anchor->removeAttribute('href');
    });
    return (string) $this->crawler;
  }

  /**
   * {@inheritdoc}
   */
  public function listAttribute($content) {
    $this->crawler->addContent($content);
    $this->links = [];
    $this->crawler->filter('a')->each(function (HtmlPageCrawler $anchor, $uri) {
      global $base_url;

      $href = $anchor->attr('href');
      try {
        $this->links[] = $base_url . $this->aliasManager->getAliasByPath($href);
      }
      catch (\Exception $e) {
        try {
          $this->links[] = $this->urlFromHref($href)->toString();
        }
        catch (\InvalidArgumentException $e) {
          // Document contains invalid URI (eg <a href="javascript:foo()">)
          // & we're not going to add that to printed output.
        }
      }
    });
    $this->crawler->remove();
    return implode(',', $this->links);
  }

  /**
   * Generate a URL object given a URL from the href attribute.
   *
   * Tries external URLs first, if that fails it will attempt
   * generation from a relative URL.
   *
   * @param string $href
   *   The URL from the href attribute.
   *
   * @return \Drupal\Core\Url
   *   The created URL object.
   */
  private function urlFromHref($href) {
    try {
      $url = Url::fromUri($href, ['absolute' => TRUE]);
    }
    catch (\InvalidArgumentException $e) {
      $url = Url::fromUserInput($href, ['absolute' => TRUE]);
    }

    return $url;
  }

}
