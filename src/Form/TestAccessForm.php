<?php

namespace Drupal\platformsh_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Platformsh\Client\PlatformClient;

/**
 * TODO: class docs.
 */
class TestAccessForm extends FormBase {

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
    $form['path'] = [
      '#type' => "textfield",
      '#title' => $this->t("Request Path"),
      '#default_value' => '',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run API Query'),
      '#button_type' => 'primary',
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('platformsh_api.settings');

    if ($form_state->hasValue('path')) {
      $path = $form_state->getValue('path');
    }

    $this->messenger()->addStatus($this->t('Ran API request.'));

  }


}
