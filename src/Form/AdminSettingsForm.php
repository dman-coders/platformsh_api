<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\platformsh_project\ApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for Platform.sh API settings
 */
class AdminSettingsForm extends ConfigFormBase {

  private ApiService $api_service;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\platformsh_project\ApiService $api_service
   */
  public function __construct(ConfigFactoryInterface $config_factory, ApiService $api_service) {
    parent::__construct($config_factory);
    $this->api_service = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Dependency injection requires that we mirror and extend ConfigFormBase.
    return new static(
      // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('platformsh_api.fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'platformsh_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => "textfield",
      '#title' => $this->t("Api Key"),
      '#default_value' => $this->api_service->getApiKey(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('platformsh_api.settings');

    if ($form_state->hasValue('api_key')) {
      $config->set('api_key', $form_state->getValue('api_key'));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['platformsh_api.settings'];
  }

}
