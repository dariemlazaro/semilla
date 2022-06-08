<?php

namespace Drupal\printable\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for printable format plugins.
 */
interface PrintableFormatInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Returns the administrative label for this format plugin.
   *
   * @return string
   *   The label of plugin.
   */
  public function getLabel();

  /**
   * Returns the administrative description for this format plugin.
   *
   * @return string
   *   The description of plugin.
   */
  public function getDescription();

  /**
   * Set the content for the printable response.
   *
   * @param array $content
   *   A render array of the content to be output by the printable format.
   */
  public function setContent(array $content);

  /**
   * Returns the response object for this format plugin.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function getResponse();

  /**
   * Get the entity we are rendering.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being rendered.
   */
  public function getEntity();

  /**
   * Set the entity we are rendering.
   *
   * @param \Drupal\Core\Entity\EntityInterface
   *   The entity being rendered.
   */
  public function setEntity(EntityInterface $entity);

}
