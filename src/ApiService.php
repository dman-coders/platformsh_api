<?php

namespace Drupal\platformsh_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Platformsh\Client\PlatformClient;

/**
 * Interacts with the API
 *
 * A Drupal service wrapper around the Platformsh API Client.
 * Also includes some data rendering helpers.
 *
 * Just grab getApiClient() and interact with that directly if you want.
 */
class ApiService {

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  private PlatformClient $api_client;

  /**
   * Return a working API client.
   *
   * Initializing api_client in the constructor didn't seem to persist,
   * so use a getter.
   *
   * @return \Platformsh\Client\PlatformClient
   */
  public function getApiClient(): PlatformClient {
    if (!empty($this->api_client)) {
      return $this->api_client;
    }
    $api_key = $this->settings->get('api_key');
    /** @var \Platformsh\Client\Model\Project $api_project */
    $this->api_client = new PlatformClient();
    $this->api_client->getConnector()->setApiToken($api_key, 'exchange');
    return $this->api_client;
  }

  /**
   * Constructs an ApiService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings = $config_factory->get('platformsh_api.settings');
  }

}
