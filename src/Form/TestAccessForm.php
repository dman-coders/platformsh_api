<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\platformsh_api\ApiService;
use GuzzleHttp\Exception\GuzzleException;
use Platformsh\Client\PlatformClient;

/**
 * Admin form for testing the API credentials and access
 *
 * Published at /admin/config/services/platformsh_api/testaccess
 */
class TestAccessForm extends FormBase {

  private ApiService $api_service;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->api_service = \Drupal::service('platformsh_api.fetcher');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'platformsh_api_testaccess_form';
  }

  /**
   * Return a working API service.
   *
   * Don't trust $this->api_service, use a getter every time,
   * as it seems that forms may sometimes skip the __construct() step.
   * as `FormBuilder` or `FormSubmitter` seems to be calling this class
   * statically or something.
   * I think it's been re-hydrated from cache so it loses its class properties.
   *
   * @return ApiService
   */
  private function getApiService(): ApiService {
    if (empty($this->api_service)) {
      $this->api_service = \Drupal::service('platformsh_api.fetcher');
    }
    return $this->api_service;
  }

  /**
   * Return a working API client.
   *
   * @return \Platformsh\Client\PlatformClient
   */
  private function getApiClient(): PlatformClient {
    return $this->getApiService()->getApiClient();
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
      $response = $this->getApiService()->getAccountInfo();
      $this->messenger()->addStatus($this->t('Ran API request.'));
      $response = $this->getApiService()->dataStructToRenderableTable($response);
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
      $response =$this->getApiService()->projectInfoToRenderable($response);
      $form_state->setValue('response', $response);
    }
    else {
      $form_state->setValue('response', ['#markup' => 'No ID provided']);
    }
    $form_state->setRebuild(TRUE);
  }

}
