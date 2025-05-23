<?php

namespace Drupal\simple_poll\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Simple Poll settings.
 */
class SimplePollSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'simple_poll_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('simple_poll.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable voting system'),
      '#description' => $this->t('Enable or disable the voting system globally.'),
      '#default_value' => $config->get('enabled') ?? TRUE,
    ];

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API Settings'),
      '#open' => TRUE,
    ];

    $form['api']['api_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable API'),
      '#description' => $this->t('Enable or disable the REST API for polls.'),
      '#default_value' => $config->get('api.enabled') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->config('simple_poll.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('api.enabled', $form_state->getValue('api_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return ['simple_poll.settings'];
  }

}
