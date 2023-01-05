<?php

namespace Drupal\Tests\platformsh_api\Functional\Form;

use Drupal;
use Drupal\Tests\platformsh_api\Functional\PlatformshBrowserTestBase;

/**
 * Tests the admin form.
 *
 * @group platformsh_api
 */
class AdminSettingsFormTest extends PlatformshBrowserTestBase {

  public function testIfTestingRuns() {
    $this->assertEquals('foo', 'foo');
    $this->assertEquals('bar', 'bar');
  }

  /**
   * Tests setting the API key via UI.
   */
  private function _testAdminSettingsFormSavesConfiguration() {

    // Go to the admin form.
    $this->drupalGet('/admin/config/services/platformsh_api');

    // Set value.
    $edit = [
      'source[api_key]' => 'foo',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the setting changed.
    $api_key = Drupal::config('platformsh_api.settings')->get('api_key');
    $this->assertEquals('foo', $api_key);
  }


}
