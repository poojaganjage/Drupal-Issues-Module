<?php
/**
 * @file
 * Contains \Drupal\custom_email_form\Form\EmailForm.
 */

namespace Drupal\custom_email_form\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Email form.
 */
class EmailForm extends FormBase {
    
    public function getFormId() {
        return 'custom_email_form_sent';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {   
        $form['to'] = array(
        '#type' => 'email',
        '#title' => $this->t('To'),
        '#required' => true,
        );
        $form['subject'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#required' => true,
        );
        $form['message'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Message'),
        '#required' => true,
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#button_type' => 'primary',
        );  
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        //Validate Message
        if (strlen($form_state->getValue('message')) > 5) {
            $form_state->setErrorByName('message', $this->t('Message is too long'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        //Submit Form
        foreach ($form_state->getValues() as $key => $value) {
            drupal_set_message($key . ': ' . $value);
        }
    }
}
