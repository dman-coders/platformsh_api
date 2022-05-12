<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;
use Platformsh\Client\Model\Project;
use Platformsh\Client\PlatformClient;

/**
 * Admin form for testing the API credentials and access
 *
 * Published at /admin/config/services/platformsh_api/testaccess
 */
class TestAccessForm extends FormBase {

  private PlatformClient $api_client;

  /**
   * Return a working API client.
   *
   * Initializing api_client in the constructor didn't seem to persist,
   * so use a getter.
   *
   * @return \Platformsh\Client\PlatformClient
   */
  private function getApiClient(): PlatformClient {
    if (!empty($this->api_client)) {
      return $this->api_client;
    }
    $api_key = $this->config('platformsh_api.settings')->get('api_key');
    /** @var \Platformsh\Client\Model\Project $api_project */
    $this->api_client = new PlatformClient();
    $this->api_client->getConnector()->setApiToken($api_key, 'exchange');
    return $this->api_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'platformsh_api_testaccess_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['actions']['getAccountInfo'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get account info'),
      '#submit' => [[$this, 'submitGetAccountInfo']],
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
    ];

    $form['actions']['getProjectInfo'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get project info'),
      '#submit' => [[$this, 'submitGetProjectInfo']],
    ];


    if ($response = $form_state->getValue('response')) {
      $form['response'] = $response;
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    # Submission actually gets processed in the buildForm phase.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitGetAccountInfo(array &$form, FormStateInterface $form_state) {
    $this->messenger()
      ->addStatus($this->t('Running API request getAccountInfo.'));
    try {
      $response = $this->getApiClient()->getAccountInfo();
      $this->messenger()->addStatus($this->t('Ran API request.'));
      #$response = $this->formatResponse($response);
      $response = $this->dataStructToRenderableTable($response);
    } catch (GuzzleException $e) {
      $this->messenger()->addStatus($this->t('Failed API request.'));
      $response = ['#markup' => $e->getMessage()];
    }
    $form_state->setValue('response', $response);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitGetProjectInfo(array &$form, FormStateInterface $form_state) {
    $projectID = $form_state->getValue('id');
    if (!empty($projectID)) {
      $this->messenger()
        ->addStatus($this->t('Running API request getProjectInfo.'));
      $response = $this->getApiClient()->getProject($projectID);
      $this->messenger()->addStatus($this->t('Ran API request.'));
      $response = $this->projectInfoToRenderable($response);
      $form_state->setValue('response', $response);
    }
    else {
      $form_state->setValue('response', ['#markup' => 'No ID provided']);
    }
    $form_state->setRebuild(TRUE);
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
    $url =  Url::fromUri($project->getProperty('uri'));
    $link = Link::fromTextAndUrl($project->getProperty('id'), $url);
    $render['id'] = $link->toRenderable();
    $properties = [
      'region' => $project->getProperty('region'),
      'plan' => $project->getProperty('plan')
    ];
    $render['properties'] = $this->dataStructToRenderableTable($properties);
    return $render;
  }

}
