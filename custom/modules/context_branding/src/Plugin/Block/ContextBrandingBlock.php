<?php

namespace Drupal\context_branding\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A custom dynamic block for site branding w/ non-link H1 frontpage title.
 *
 * @Block(
 *   id = "context_branding_block",
 *   admin_label = @Translation("Contextual Branding"),
 *   category = @Translation("Misc"),
 * )
 */
class ContextBrandingBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * For service dependency injection.
   *
   * @var Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * For service dependency injection.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param Drupal\Core\Path\PathMatcher $path_matcher
   *   Path matcher service dependency injection.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory service dependency injection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PathMatcher $path_matcher,
    ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pathMatcher = $path_matcher;
    $this->configFactory = $config_factory;
  }

  /**
   * Object create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container interface.
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.matcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $is_front = $this->pathMatcher->isFrontPage();
    $site_config = $this->configFactory->get('system.site');
    $site_name = $site_config->get('name');
    $site_slogan = $site_config->get('slogan');

    if ($is_front) {
      $site_title = "
        <h1 class='site-title'>
          $site_name
        </h1>
      ";
    }
    else {
      $site_title = "
        <a href='/' title='Home' rel='home' class='site-title'>
          $site_name
        </a>
      ";
    }

    $markup = "
      <div class='contextual-region block block-system block-system-branding-block'>
        <div class='navbar-brand d-flex align-items-center'>
          <div>
            $site_title
            <div class='site-slogan'>
              $site_slogan
            </div>
          </div>
        </div>
      </div>
    ";

    return [
      '#markup' => $this->t($markup),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
