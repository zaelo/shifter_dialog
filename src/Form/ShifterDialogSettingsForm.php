<?php

namespace Drupal\shifter_dialog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Configure Shifter Dialog settings.
 */
class ShifterDialogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shifter_dialog_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shifter_dialog.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shifter_dialog.settings');

    $form['results_configuration'] = array(
      '#type' => 'details',
      '#title' => $this->t('Results configuration'),
      '#open' => TRUE,
    );

    $form['results_configuration']['number_results'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of results to return'),
      '#default_value' => $config->get('results.number_to_return'),
      '#required' => TRUE,
    );

    $form['results_configuration']['menus_depth'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum depth search'),
      '#default_value' => $config->get('results.menus_depth'),
      '#required' => TRUE,
    );

    $form['results_configuration']['no_matches_sentence'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('No matches sentence'),
      '#default_value' => $config->get('results.no_matches_sentence'),
      '#maxlength' => 100,
      '#required' => TRUE,
    );

    $form['menus_configuration'] = array(
      '#type' => 'details',
      '#title' => $this->t('Menus'),
    );

    $form['menus_configuration']['menus_to_enable'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Search only on those menus'),
      '#options' => $this->getCustomMenusNames(),
      '#default_value' => !empty($config->get('menus_names')) ? $config->get('menus_names') : $this->getCustomMenusNames(),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $number_results = (int) $form_state->getValue('number_results');
    $menus_depth = (int) $form_state->getValue('menus_depth');
    if (!is_int($number_results) || $number_results < 1) {
      $form_state->setErrorByName('number_results', $this->t('The number of results to return must be greater than 0.'));
    }
    if (!is_int($menus_depth) || $menus_depth < 1) {
      $form_state->setErrorByName('menus_depth', $this->t('The maximum depth search must be greater than 0.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shifter_dialog.settings')
      ->set('results.number_to_return', $form_state->getValue('number_results'))
      ->set('results.menus_depth', $form_state->getValue('menus_depth'))
      ->set('results.no_matches_sentence', $form_state->getValue('no_matches_sentence'))
      ->set('menus_names', $form_state->getValue('menus_to_enable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return an associative array of the custom menus names.
   *
   * @return array
   *   An array with the machine-readable names as the keys, and human-readable
   *   titles as the values.
   */
  public function getCustomMenusNames() {
    if ($custom_menus = Menu::loadMultiple()) {
      foreach ($custom_menus as $menu_name => $menu) {
        $custom_menus[$menu_name] = $menu->label();
      }
      asort($custom_menus);
    }
    return $custom_menus;
  }

}
