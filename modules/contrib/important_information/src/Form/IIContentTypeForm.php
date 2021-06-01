<?php

namespace Drupal\important_information\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IIContentTypeForm.
 */
class IIContentTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ii_content_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ii_content_type->label(),
      '#description' => $this->t("Label for the II Content type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ii_content_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\important_information\Entity\IIContentType::load',
      ],
      '#disabled' => !$ii_content_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ii_content_type = $this->entity;
    $status = $ii_content_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label II Content Type.', [
          '%label' => $ii_content_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label II Content Type.', [
          '%label' => $ii_content_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ii_content_type->toUrl('collection'));
  }

}
