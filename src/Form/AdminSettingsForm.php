<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * TODO: class docs.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'platformsh_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('platformsh_api.settings');

    $form['api_key'] = [
      '#type' => "textfield",
      '#title' => $this->t("Api Key"),
      '#default_value' => $config->get('api_key'),
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
  protected function getEditableConfigNames() {
    return ['platformsh_api.settings'];
  }

}
