<?php

namespace Drupal\printable\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\printable\PrintableLinkBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Cache\Cache;

/**
 * Provides a printable links block for each printable entity.
 *
 * @Block(
 *   id = "printable_links_block",
 *   admin_label = @Translation("Printable Links Block"),
 *   category = @Translation("Printable"),
 *   deriver = "Drupal\printable\Plugin\Derivative\PrintableLinksBlock"
 * )
 */
class PrintableLinksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routematch;

  /**
   * The printable link builder.
   *
   * @var \Drupal\printable\PrintableLinkBuilderInterface
   */
  protected $linkBuilder;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routematch
   *   The route match interface.
   * @param \Drupal\printable\PrintableLinkBuilderInterface $link_builder
   *   The printable link builder.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Provides a service to handle various date related functionality.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routematch, PrintableLinkBuilderInterface $link_builder, DateFormatter $dateFormatter, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routematch = $routematch;
    $this->linkBuilder = $link_builder;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('printable.link_builder'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'max_age' => 180,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form += parent::buildConfigurationForm($form, $form_state);
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([
      $this->dateFormatter,
      'formatInterval',
    ], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';
    $period[Cache::PERMANENT] = $this->t('Forever');
    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache settings'),
    ];
    $form['cache']['max_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum age'),
      '#description' => $this->t('The maximum time this block may be cached.'),
      '#default_value' => $this->configuration['max_age'],
      '#options' => $period,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('cache');
    $this->configuration['max_age'] = $settings['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity_type = $this->getDerivativeId();
    $route_match = $this->routematch->getMasterRouteMatch();
    $route_entity = $route_match->getParameter($entity_type);
    $entity_id = $route_entity ? $route_entity->id() : 'NULL';

    $build = [
      '#cache' => [
        'contexts' => ['route'],
        'tags' => [$entity_type . ':' . $entity_id],
        'max-age' => $this->configuration['max_age'] ?: 0,
      ],
    ];

    if ($route_entity) {
      return $build + [
        '#theme' => 'links__entity__printable',
        '#links' => $this->linkBuilder->buildLinks($this->routematch->getMasterRouteMatch()
          ->getParameter($entity_type)),
      ];
    }
    else {
      return $build;
    }
  }

}
