<?php
/**
 * @file
 * Contains \Drupal\amazing_forms\Form\ContributeForm.
 */

namespace Drupal\amazing_forms\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class ContributeForm extends FormBase
{
    public function getFormId() 
    {
        return 'amazing_forms_contribute';
    }

    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        $form['fieldsetelement'] = array(
        // '#type' => 'label',
        '#markup' => '<p>'.$this->t('Title of the Fieldset').'</p>',
        );
        $form['candidate_name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Candidate Name:'),
        '#required' => true,
        );
        $form['candidate_mail'] = array(
        '#type' => 'email',
        '#title' => $this->t('Email ID:'),
        '#required' => true,
        );
        $form['candidate_number'] = array (
        '#type' => 'tel',
        '#title' => $this->t('Mobile no:'),
        '#required' => true,
        );
        $form['candidate_dob'] = array (
        '#type' => 'date',
        '#title' => $this->t('DOB:'),
        '#required' => true,
        );
        $form['candidate_gender'] = array (
        '#type' => 'select',
        '#title' => ('Gender:'),
        '#options' => array(
        'Female' => $this->t('Female'),
        'male' => $this->t('Male'),
        ),
        );
        $form['candidate_confirmation'] = array (
        '#type' => 'radios',
        '#title' => ('Are you above 18 years old?'),
        '#options' => array(
        'Yes' => $this->t('Yes'),
        'No' => $this->t('No')
        ),
        );
        $form['candidate_copy'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Send me a copy of the application.'),
        );
        // $form['my_captcha_element'] = array(
        // '#type' => 'captcha',
        // '#captcha_type' => 'recaptcha/reCAPTCHA',
        // );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
        );
        
    //     $build = [
    //     '#theme' => 'amazing_forms_fieldset_element',
    //     '#items' => $form,
    // ];

        $form['#theme'] = 'fieldset_element';
      
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
        // Validate video URL.
        if (strlen($form_state->getValue('candidate_number')) < 10) {
            $form_state->setErrorByName('candidate_number', $this->t('Mobile number is too short.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        // Display result.
        // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
        foreach ($form_state->getValues() as $key => $value) {
            drupal_set_message($key . ': ' . $value);
        }
    }
}
