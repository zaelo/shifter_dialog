<?php

namespace Drupal\shifter_dialog\Plugin\Block;

use Drupal\shifter_dialog\MenusListInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'Shifter Dialog' block.
 *
 * @Block(
 *   id = "shifter_dialog_form_block",
 *   admin_label = @Translation("Shifter Dialog form"),
 *   category = @Translation("Forms")
 * )
 */
class ShifterDialogBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The MenusList service.
   *
   * @var \Drupal\shifter_dialog\MenusList
   */
  protected $menusListGenerator;

  /**
   * The FormBuilder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * The ConfigFactory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenusListInterface $menus_list_generator, FormBuilderInterface $formBuilder, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menusListGenerator = $menus_list_generator;
    $this->formBuilder = $formBuilder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('shifter_dialog.menus_list'),
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'use shifter dialog');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $shifter_dialog_config = $this->configFactory->get('shifter_dialog.settings');
    $shifter_dialog_block = array();

    $shifter_dialog_block['shifter_dialog_form'] = $this->formBuilder->getForm('Drupal\shifter_dialog\Form\ShifterDialogBlockForm');
    $shifter_dialog_block['results'] = array(
      '#prefix' => '<ul class="shifter-dialog-results">',
      '#markup' => '<li class="shifter-dialog-no-matches">' . $shifter_dialog_config->get('results.no_matches_sentence') . '</li>',
      '#suffix' => '</ul>',
    );
    $shifter_dialog_block['help_text'] = array(
      '#prefix' => '<p class="shifter-dialog-help-text">',
      '#markup' => $this->t('Pressing period (.) opens this dialog box'),
      '#suffix' => '</p>',
    );
    $shifter_dialog_block['#attributes']['class'][] = 'block-shifter-dialog';

    $shifter_dialog_block['#attached']['drupalSettings']['shifterDialog'] = array(
      'menusList' => $this->menusListGenerator->getJsonMenusList(),
      'numResultsToReturn' => $shifter_dialog_config->get('results.number_to_return'),
      'noMatchesSentence' => $shifter_dialog_config->get('results.no_matches_sentence'),
    );

    return $shifter_dialog_block;
  }

}
