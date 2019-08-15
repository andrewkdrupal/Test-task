<?php

namespace Drupal\task_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TaskForm extends FormBase {

  public function getFormId() {

    return 'task_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['firstName'] = [
      '#title' => $this->t('First Name: '),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['lastName'] = [
      '#title' => $this->t('Last Name: '),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#title' => $this->t('Subject: '),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#title' => $this->t('Message: '),
      '#type' => 'textarea',
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#title' => $this->t('E-mail: '),
      '#type' => 'email',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $emailInput = $form_state->getValue('email');

    if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('email', $this->t('The email must be valid!'));
    }

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $mailManager = \Drupal::service('plugin.manager.mail');

    $module = 'task_module';
    $key = 'send_email';
    $to = $form_state->getValue('email');
    $params['message'] = $form_state->getValue('message');
    $params['subject'] = $form_state->getValue('subject');
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if ($result['result'] !== true) {
      drupal_set_message(t('The message was not sent, email address might be incorrect'), 'error');
    } else {
      drupal_set_message(t('The message has been sent.'));
      \Drupal::logger('task_module')->notice('The message to ' . $to . ' was sent successfully.');
    }


    $firstnameInput = $form_state->getValue('firstName');
    $lastNameInput = $form_state->getValue('lastName');
    
    $this->createContact($to, $firstnameInput, $lastNameInput);

    $form_state->setRedirect('<front>');
  }


  public function createContact($email, $firstName, $lastName) {

    $arr = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value' => $email
                ),
                array(
                    'property' => 'firstname',
                    'value' => $firstName
                ),
                array(
                    'property' => 'lastname',
                    'value' => $lastName
                ),
                array(
                    'property' => 'phone',
                    'value' => ''
                )
            )
        );
    $post_json = json_encode($arr);

    $hapikey = '2e30a850-17e8-4b88-b920-08d3072399f9';

    $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $hapikey;
    $ch = @curl_init();
    @curl_setopt($ch, CURLOPT_POST, true);
    @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
    @curl_setopt($ch, CURLOPT_URL, $endpoint);
    @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = @curl_exec($ch);
    $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errors = curl_error($ch);
    @curl_close($ch);
    echo "curl Errors: " . $curl_errors;
    echo "\nStatus code: " . $status_code;
    echo "\nResponse: " . $response;
  }

}
