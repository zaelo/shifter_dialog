<?php

namespace Drupal\shifter_dialog;

/**
 * Defines an interface for pulling MenusList dependencies from the container.
 */
interface MenusListInterface {

  /**
   * Returns the list of menus in the JSON format.
   */
  public function getJsonMenusList();

}
