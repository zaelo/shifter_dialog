<?php

namespace Drupal\shifter_dialog;

/**
 * Provides a couple of menu link tree manipulators.
 */
class ShifterDialogTreeManipulators {

  /**
   * Generates an index and sorts by it.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function generateIndexAndSort(array $tree) {
    $new_tree = array();
    foreach ($tree as $key => $v) {
      if ($tree[$key]->subtree) {
        $tree[$key]->subtree = $this->generateIndexAndSort($tree[$key]->subtree);
      }
      $instance = $tree[$key]->link;

      $new_tree[$instance->getTitle() . ' ' . $instance->getPluginId()] = $tree[$key];
    }
    ksort($new_tree);
    return $new_tree;
  }

}
