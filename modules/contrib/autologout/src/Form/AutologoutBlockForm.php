<?php

/**
 * Provides autologout block form Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license www.google.com ABC
 *
 * @version "GIT: <1001>"
 *
 * @link www.google.com
 */

namespace Drupal\autologout\Form;

use Drupal\autologout\AutologoutManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides autologout block form Implementation.
 *
 * @category Module
 *
 * @package Contrib
 *
 * @author Display Name <username@example.com>
 *
 * @license www.google.com ABC
 *
 * @version "Release: 8"
 *
 * @link www.google.com
 */
class AutologoutBlockForm extends FormBase
{

    /**
     * The autologout manager service.
     *
     * @var \Drupal\autologout\AutologoutManagerInterface
     */
    protected $autoLogoutManager;

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function getFormId()
    {
        return 'autologout_block_settings';
    }

    /**
     * Constructs an AutologoutBlockForm object.
     *
     * @param $autologout The autologout manager service.
     */
    public function __construct(AutologoutManagerInterface $autologout)
    {
        $this->autoLogoutManager = $autologout;
    }

    /**
     * {@inheritdoc}
     *
     * @param $container The container variable.
     *
     * @return object 
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('autologout.manager')
        );
    }

    /**
     * Defines form and form state interface and build form.
     *
     * Build the form using $form varibale using.
     *
     * @param $form       Build the form using $form varibale using.
     * @param $form_state Build the form using $form_state interface.
     *
     * @return $form
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['reset'] = [
        '#type' => 'button',
        '#value' => $this->t('Reset Timeout'),
        '#weight' => 1,
        '#limit_validation_errors' => false,
        '#executes_submit_callback' => false,
        '#ajax' => [
        'callback' => 'autologout_ajax_set_last',
        ],
        ];

        $form['timer'] = [
        '#markup' => $this->autoLogoutManager->createTimer(),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Build the form using $form varibale using.
     *
     * @param $form       Build the form using $form varibale using.
     * @param $form_state Build the form using $form_state interface.
     *
     * @return object
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Submits on block form.
    }

}
