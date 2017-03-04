<?php

namespace Drupal\shifter_dialog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds form input for the Shifter Dialog block.
 */
class ShifterDialogBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shifter_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['keys'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Keys'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#attributes' => array(
        'title' => $this->t('Enter the terms you wish to search for.'),
        'class' => array('shifter-dialog-keys'),
        'placeholder' => $this->t('Find Actions...'),
        'autocomplete' => 'off',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
