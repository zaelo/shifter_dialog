<?php

namespace Drupal\shifter_dialog;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\system\Entity\Menu;

/**
 * Provides a list of menus.
 */
class MenusList implements MenusListInterface {

  /**
   * The menu link tree storage.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The ConfigFactory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * MenusList constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The ConfigFactory object.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menus_link_tree
   *   The menu link tree storage.
   */
  public function __construct(ConfigFactoryInterface $config, MenuLinkTreeInterface $menus_link_tree) {
    $this->configFactory = $config;
    $this->menuTree = $menus_link_tree;
  }

  /**
   * Returns the JSON format of the menus list.
   *
   * @return string
   *   The list of menus in the JSON format.
   */
  public function getJsonMenusList() {
    $menus_list = $this->flattenMenus($this->getMenusList());
    usort($menus_list, array($this, 'titleSorting'));
    return json_encode($menus_list);
  }

  /**
   * Returns menus list.
   *
   * @return array
   *   An array with the machine-readable names as the keys, and the menu items
   *   as the values.
   */
  public function getMenusList() {
    $menus_depth = (int) $this->configFactory->get('shifter_dialog.settings')->get('results.menus_depth');
    $menus_tree_parameters = new MenuTreeParameters();
    $menus_tree_parameters->setMaxDepth($menus_depth);

    $menus_list = array();
    $menus_machine_names = $this->getMenusMachineNames();
    foreach ($menus_machine_names as $machine_name) {
      $menus_list[$machine_name] = $this->getMenuItems($machine_name, $menus_tree_parameters);
    }

    return $menus_list;
  }

  /**
   * Returns menu items.
   *
   * @param string $menu_machine_name
   *   The menu machine name.
   * @param \Drupal\Core\Menu\MenuTreeParameters $menus_tree_parameters
   *   A value object to model menu tree parameters.
   *
   * @return array
   *   An array containing the menu items with their titles and urls.
   */
  public function getMenuItems($menu_machine_name, MenuTreeParameters $menus_tree_parameters) {
    $tree = $this->menuTree->load($menu_machine_name, $menus_tree_parameters);
    $manipulators = array(
      // Only show links that are accessible for the current user.
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // Flattens the tree to a single level.
      array('callable' => 'menu.default_tree_manipulators:flatten'),
      // Use the custom generateIndexAndSort sorting of menu links.
      array('callable' => 'shifter_dialog.tree_manipulators:generateIndexAndSort'),
    );

    return $this->buildMenuContent($this->menuTree->transform($tree, $manipulators));
  }

  /**
   * Builds the menu structure on which depends the search.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return array
   *   An associative array containing the menu items to look in.
   */
  public function buildMenuContent(array $tree) {
    $menu_elements = array();
    foreach ($tree as $item) {
      $menu_title = $item->link->getTitle();
      $route_url = $item->link->getUrlObject()->toString();
      $menu_elements[] = array(
        'title' => ucfirst($menu_title),
        'url' => $route_url,
      );
      $this->buildSubStrings($menu_elements, $menu_title, $route_url);
    }
    return $menu_elements;
  }

  /**
   * Flattens menus to a single level.
   *
   * @param array $menus_list
   *   The menus list.
   *
   * @return array
   *   A single level menus.
   */
  public function flattenMenus(array $menus_list) {
    $new_list = array();
    foreach ($menus_list as &$value) {
      $new_list = array_merge($new_list, $value);
    }
    return $new_list;
  }

  /**
   * Builds pieces of the menus titles.
   *
   * @param array $menu_elements
   *   The menu items built.
   * @param string $menu_title
   *   The menu title.
   * @param string $route_url
   *   The route url of the menu item.
   *
   * @see buildMenuContent()
   */
  public function buildSubStrings(array &$menu_elements, $menu_title, $route_url) {
    $spliting_title = explode(' ', $menu_title);
    for ($i = 1; $i < count($spliting_title); $i++) {
      $menu_elements[] = array(
        'full_title' => $menu_title,
        'title' => ucfirst(implode(' ', array_slice($spliting_title, $i))),
        'url' => $route_url,
      );
    }
  }

  /**
   * Returns the custom menus machine names.
   *
   * @return array
   *   An array with the machine-readable names of the menus to look in.
   */
  public function getMenusMachineNames() {
    $machine_names = [];

    if ($menus_machine_names = $this->configFactory->get('shifter_dialog.settings')->get('menus_names')) {
      foreach ($menus_machine_names as $menu_name) {
        if (!empty($menu_name)) {
          $machine_names[] = $menu_name;
        }
      }
    }

    if (empty($machine_names) && $custom_menus = Menu::loadMultiple()) {
      foreach ($custom_menus as $menu_name => $menu) {
        $machine_names[] = $menu_name;
      }
    }

    return $machine_names;
  }

  /**
   * A Callback method used to sort the menus.
   *
   * @see getJsonMenusList()
   */
  private function titleSorting($a, $b) {
    return strnatcmp($a['title'], $b['title']);
  }

}
