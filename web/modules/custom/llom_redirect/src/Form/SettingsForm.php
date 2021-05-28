<?php

namespace Drupal\llom_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase{

  const SETTINGS='llom_redirect.settings';

  public function getFormId(){
    return 'llom_redirect_admin_settings';
  }

  protected function getEditableConfigNames(){
    return[
      static::SETTINGS,
    ];
  }

 /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['redirect_url'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('LLOM React App URL'),
      '#description' => t('Add a valid url or / for main page to redirect Students. You can add the url of the React App to redirect after logout.'),
      '#default_value' => $config->get('redirect_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('redirect_url', $form_state->getValue('redirect_url'))

      ->save();

    parent::submitForm($form, $form_state);
  }

}
