<?php

namespace Drupal\platformsh_api\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Platformsh\Client\Model\Project;
use Platformsh\Client\PlatformClient;

/**
 * TODO: class docs.
 */
class TestAccessForm extends FormBase {

  private $api_client;

  /**
   * Initializing api_client in the constructor didn't seem to persist.
   *
   * @return \Platformsh\Client\PlatformClient
   */
  private function getApiClient() {
    if (!empty($this->api_client)) {
      return $this->api_client;
    }
    $config = $this->config('platformsh_api.settings');
    $api_key = Drupal::config('platformsh_api.settings')->get('api_key');
    /** @var \Platformsh\Client\Model\Project $api_project */
    $this->api_client = new PlatformClient();
    $this->api_client->getConnector()->setApiToken($api_key, 'exchange');
    return $this->api_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'platformsh_api_testaccess_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

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
    # Submission actually gets processed in the buildform phase.
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
    $response = $this->getApiClient()->getAccountInfo();
    $this->messenger()->addStatus($this->t('Ran API request.'));
    $response = $this->formatResponse($response, $keys = ['id', 'title']);
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
      $response = $this->formatProjectInfo($response, $keys = ['id', 'title']);
      $form_state->setValue('response', $response);
      $form_state->setRebuild(TRUE);
    }
    else {
      $form_state->setValue('response', ['#markup' => 'No ID provided']);
      $form_state->setRebuild(TRUE);
    }
  }


  /**
   * Format the JSON into something renderable
   */
  public function formatResponse($response) {
    $markup = '<div id="my-custom-markup"><h1>Here is my markup</h1><pre>' . print_r($response, TRUE) . '</pre></div>';
    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

  /**
   * Format the JSON into something renderable.
   *
   * @return a render array
   */
  public function formatTable($response, $keys = ['id', 'title']) {
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
   * Format the JSON into something renderable
   *
   * @param $project Project
   */
  public function formatProjectInfo($project) {
    $render = [];
    $render['title']['#markup'] = ['<h1>' . $project->title . '</h1>'];
    $render['id']['#markup'] = ['<h2>' . $project->id . '</h2>'];
    return $render;
  }

}
