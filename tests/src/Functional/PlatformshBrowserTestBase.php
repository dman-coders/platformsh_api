<?php

namespace Drupal\Tests\platformsh_api\Functional;

use Drupal\feeds\FeedInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Provides a base class for Feeds functional tests.
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
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function _setUp(): void {
    parent::setUp();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'access feed overview',
    ]);
    $this->drupalLogin($this->adminUser);
  }


}
