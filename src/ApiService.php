<?php

namespace Drupal\platformsh_api;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Platformsh\Client\Model\Project;
use Platformsh\Client\PlatformClient;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Interacts with the API
 *
 * A Drupal service wrapper around the Platformsh API Client.
 * Also includes some data rendering helpers.
 *
 * Just grab getApiClient() and interact with that directly if you want.
 */
class ApiService {
  use LoggerChannelTrait;

  /**
   * The settings.
   *
   * @var Config
   */
  protected Config $settings;

  private PlatformClient $api_client;

  /**
   * Fetch the appropriate API key
   * either from the settings or the environment variables.
   * Drupal settings takes priority over environment variables.
   *
   * @return string
   */
  public function getApiKey() {
    $api_key = $this->settings->get('api_key');
    if (!empty($api_key)) {
      return $api_key;
    }
    $api_key = getenv('PLATFORMSH_API_TOKEN');
    if (empty($api_key)) {
      $url = Url::fromRoute('platformsh_api.settings');
      $settings_link = Link::fromTextAndUrl('Platformsh API settings', $url)->toString();
      throw new \RuntimeException("No Platform.sh API key available. Please add a valid API key in the $settings_link or set PLATFORMSH_API_TOKEN as an environment variable.");
    }
    return $api_key;
  }

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
    $api_key = $this->getApiKey();

    /** @var \Platformsh\Client\Model\Project $api_project */
    $this->api_client = new PlatformClient();
    $this->api_client->getConnector()->setApiToken($api_key, 'exchange');
    return $this->api_client;
  }

  /**
   * Get account information for the logged-in user.
   *
   * STUB for the ApiClient that adds logging
   *
   * @param bool $reset
   *
   * @return array
   */
  public function getAccountInfo(bool $reset = false): array {
    $this->getLogger('platformsh')->notice('Running API request getAccountInfo.');
    try {
      return $this->getApiClient()->getAccountInfo($reset);
    } catch (\Exception|GuzzleException $e) {
      $this->getLogger('platformsh')->notice('Failed to retried Account Info: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get a single project by its ID.
   *
   * STUB to passthrough with extra logging.
   *
   * @param string $id
   * @param string|null $hostname
   * @param bool $https
   *
   * @return Project|false
   */
  public function getProject(string $id, string $hostname = null, bool $https = true): Project|bool {
    $this->getLogger('platformsh')->notice('Running API request getProject.');
    $response = $this->getApiClient()->getProject($id, $hostname, $https);
    $this->getLogger('platformsh')->notice('Completed API request getProject.');
    return $response;
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

  /**
   * Format the JSON into something render-able
   */
  public function rawDataToRenderable($response): array {
    $markup = '<div id="my-custom-markup"><h1>Here is my markup</h1><pre>' . print_r($response, TRUE) . '</pre></div>';
    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

  /**
   * Format the JSON into something render-able.
   *
   * @param array $data a nested data structure
   * @param array $keys The data keys to display, show all if undefined.
   *
   *
   * @return array a render array
   */
  public function dataStructToRenderableTable(array $data, array $keys = []): array {
    $rows = [];
    if (!empty($keys)) {
      foreach ($keys as $row_key) {
        if (empty($data[$row_key])) {
          continue;
        }
        $rows[$row_key] = $this->keyValRowToRenderable($row_key, $data[$row_key]);
      }
    }
    else {
      foreach ($data as $row_key => $row_val) {
        $rows[$row_key] = $this->keyValRowToRenderable($row_key, $row_val);
      }
    }
    return [
      '#type' => 'table',
      '#rows' => $rows,
    ];
  }

  /**
   * Unpack a nested data struct and re-pack it into nested table cells.
   *
   * @param $row_key
   * @param $row_val
   *
   * @return array
   */
  function keyValRowToRenderable($row_key, $row_val): array {
    $row = [
      'key' => [
        'data' => $row_key,
        'style' => 'vertical-align:top; font-weight:bold;'
      ],
    ];
    if (!is_array($row_val)) {
      $row['value'] = $row_val;
    }
    else {
      #$row['value']['data'] = $this->formatTable($row_val);
      // Wrap the nested table content into a collapsible.
      $title = empty($row_val['title']) ? count($row_val) . ' ' . $row_key : $row_val['title'];
      $row['value']['data'] = [
        '#type' => 'details',
        '#title' => $title,
        '#description' => $this->dataStructToRenderableTable($row_val)
      ];
    }
    // If labelling is working well enough,
    // don't need to render the counter column at all.
    if (is_numeric($row_key)) {
      #unset($row['key']['data']);
      unset($row['key']);
    }
    return $row;
  }

  /**
   * Format the JSON into something render-able
   *
   * @param $project Project
   *
   * @return array
   */
  public function projectInfoToRenderable(Project $project): array {
    $render = [];
    $render['title']['#markup'] = '<h1>' . $project->getProperty('title') . '</h1>';

    $url =  Url::fromUri($project->getLink('#ui'));
    $link = Link::fromTextAndUrl($project->getProperty('id'), $url);
    $render['id'] = [
      '#type' => 'container',
      'link' => $link->toRenderable()
    ];

    $subscription = $project->getProperty('subscription');
    $subscription_link = Link::fromTextAndUrl('license', Url::fromUri($subscription['license_uri']));
    $render['subscription'] = [
      '#type' => 'container',
      'link' => $subscription_link->toRenderable()
    ];

    $properties = [
      'plan' => $subscription['plan'],
      'region' => $project->getProperty('region'),
      'environments' => $subscription['environments'],
      'storage' => $subscription['storage'],
      'included users' => $subscription['included_users'],
    ];
    $render['properties'] = $this->dataStructToRenderableTable($properties);

    return $render;
  }

  /**
   * Utility lookup function. Does not belong here.
   *
   */
  public static function getEntityById(string $id) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'field_id' => $id,
      ]);
    if (count($nodes) > 1) {
      throw new \InvalidArgumentException(t("More than one match for ID:%id found", ['id' => $id]));
    }
    return array_pop($nodes);
  }


}
