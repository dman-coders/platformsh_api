<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Platformsh\Client\Model\Project;
use Platformsh\Client\PlatformClient;

/**
 * TODO: class docs.
 */
class TestAccessForm extends FormBase {

  private PlatformClient $api_client;

  /**
   * Initializing api_client in the constructor didn't seem to persist.
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
      $response = $this->formatResponse($response);
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
      $response = $this->formatProjectInfo($response);
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
  public function formatResponse($response): array {
    $markup = '<div id="my-custom-markup"><h1>Here is my markup</h1><pre>' . print_r($response, TRUE) . '</pre></div>';
    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

  /**
   * Format the JSON into something render-able.
   *
   * @return array a render array
   */
  public function formatTable($response, $keys = ['id', 'title']): array {
    $output_array = [
      '#type' => 'table',
      '#rows' => [],
    ];
    foreach ($response as $response_row) {
      $key = $response_row->$keys[0];
      $rows = [];
      foreach ($keys as $key_id) {
        if (!is_array($response_row[$key_id])) {
          $rows[$key_id] = ['data' => $response_row[$key_id]];
        }
        else {
          $rows[$key_id] = ['data' => 'Array'];
        }
      }
      $output_array['#rows'][$key] = [
        'data' => $rows,
      ];
    }
    return $output_array;
  }

  /**
   * Format the JSON into something render-able
   *
   * @param $project Project
   *
   * @return array
   */
  public function formatProjectInfo(Project $project): array {
    $render = [];
    $render['title']['#markup'] = ['<h1>' . $project->getProperty('title') . '</h1>'];
    $render['id']['#markup'] = ['<h2>' . $project->getProperty('id') . '</h2>'];
    return $render;
  }

}
