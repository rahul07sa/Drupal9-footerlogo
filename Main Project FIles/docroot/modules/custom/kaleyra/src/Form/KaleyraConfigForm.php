<?php

namespace Drupal\kaleyra\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Kaleyra.
 */
class KaleyraConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kaleyra.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaleyra_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('kaleyra.settings');

    $form['api_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Domain Url'),
      '#description' => $this->t('Add API Domain for kaleyra SMS API integration. e.g., https://api.ap.kaleyra.io.'),
      '#default_value' => $config->get('api_domain'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The api key given to you when you <a href=":url">register for Kaleyra</a>.', [':url' => 'https://kaleyra.io/signup']),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['api_version'] = [
      '#type' => 'select',
      '#title' => $this->t('API Version'),
      '#options' => [
        'v4' => 'v4',
      ],
      '#description' => $this->t('The API version which will be used while sending messages. Currently the module only supports V4. Hence, this field stays disabled.'),
      '#default_value' => $config->get('api_version'),
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['sender_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMS Sender Identifier'),
      '#description' => $this->t('Identifier string for the sender using this service. All SMS will appear to have come from this sender.'),
      '#default_value' => $config->get('sender_identifier'),
      '#required' => TRUE,
    ];

    $form['unicode'] = [
      '#type' => 'select',
      '#options' => [
        0 => $this->t('0'),
        1 => $this->t('1'),
        'auto' => $this->t('auto'),
      ],
      '#title' => $this->t('Unicode mode'),
      '#description' => $this->t('Set the unicode mode for the message. Recommended to set to auto for sending SMS in different languages.'),
      '#default_value' => $config->get('unicode'),
      '#required' => TRUE,
    ];

    $form['whatsapp_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WhatsApp Business Number'),
      '#description' => $this->t('Number to use for FROM for WhatsApp.'),
      '#default_value' => $config->get('whatsapp_from'),
      '#required' => TRUE,
    ];

    $form['whatsapp_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WhatsApp API Key'),
      '#description' => $this->t('API key to use for WhatsApp, leave empty to use default one.'),
      '#default_value' => $config->get('whatsapp_api_key'),
      '#required' => FALSE,
    ];

    $form['whatsapp_sid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WhatsApp SID'),
      '#description' => $this->t('Account SID (Security Identifier) for WhatsApp. Generated by Kaleyra.io while creating an API key.'),
      '#default_value' => $config->get('whatsapp_sid'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('kaleyra.settings');

    $config
      ->set('api_domain', $form_state->getValue('api_domain'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('sender_identifier', $form_state->getValue('sender_identifier'))
      ->set('unicode', $form_state->getValue('unicode'))
      ->set('whatsapp_from', $form_state->getValue('whatsapp_from'))
      ->set('whatsapp_api_key', $form_state->getValue('whatsapp_api_key'))
      ->set('whatsapp_sid', $form_state->getValue('whatsapp_sid'));

    // API version is disabled so let's not set empty value if not available.
    if ($form_state->getValue('api_version')) {
      $config->set('api_version', $form_state->getValue('api_version'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
