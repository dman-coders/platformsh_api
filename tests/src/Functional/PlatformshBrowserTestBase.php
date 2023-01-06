<?php

namespace Drupal\Tests\platformsh_api\Functional;

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
    'platformsh_api'
  ];

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
      'create project',
    ]);
    $this->drupalLogin($this->adminUser);
  }


}
