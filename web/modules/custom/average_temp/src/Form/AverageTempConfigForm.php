<?php

namespace Drupal\average_temp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form.
 */
class AverageTempConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'average_temp_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['average_temp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api key'),
      '#required' => TRUE,
      '#description' => $this->t('Api key for openweathermap.org'),
      '#default_value' => $this->config('average_temp.settings')->get('key'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('average_temp.settings')
      ->set('key', $form_state->getValue('key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
