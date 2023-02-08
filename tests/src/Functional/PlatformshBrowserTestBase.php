<?php

namespace Drupal\Tests\platformsh_api\Functional;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\platformsh_api\ApiService;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Provides a base class for functional tests.
 */
abstract class PlatformshBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'platformsh_api',
  ];

  /**
   * Will use this sometimes
   *
   * @var \Drupal\platformsh_api\ApiService
   */
  protected $api_service;

  /**
   * Testing the API requires genuine credentials.
   * These must be set in the environment before these tests can run.
   */
  protected $TestAPiToken;

  /**
   * Define your own project to test against. One you have access permissions for.
   */
  protected $TestProjectId;
  /**
   * The Test Project Name acts as a verification check to ensure that we are not testing the wrong project.
   */
  protected $TestProjectName;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;


  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Normal testing setup starts off with install profile = 'testing';
    // This is sparse.
    parent::setUp();

    // Create a user with admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer platformsh_api',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->api_service = Drupal::service('platformsh_api.fetcher');
    $this->TestAPiToken = $this->api_service->getApiKey();
    if (empty($this->TestAPiToken)) {
      trigger_error("Some API tests require a valid PLATFORMSH_API_TOKEN to run. This value was not detected in the current environment. Some tests will fail.", E_USER_WARNING);
    }
    $this->TestProjectId = getenv('PLATFORM_PROJECT');
    if (empty($this->TestProjectId)) {
      trigger_error("Some API tests require a valid PLATFORM_PROJECT to run against. This value was not detected in the current environment. Some tests will fail.", E_USER_WARNING);
    }
    $this->TestProjectName = getenv('PLATFORM_PROJECT_NAME');
    if (empty($this->TestProjectId)) {
      trigger_error("Some API tests require a valid PLATFORM_PROJECT_NAME to run against. This value was not detected in the current environment. To avoid accidentally running tests against the wrong project, you must set PLATFORM_PROJECT_NAME to match the Project ID as a verification. If the API data does not match as expected, tests will be aborted.", E_USER_WARNING);
    }

  }


}
