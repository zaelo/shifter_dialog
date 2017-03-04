<?php

namespace Drupal\shifter_dialog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for shifter dialog routes.
 */
class ShifterDialogController extends ControllerBase {

  /**
   * Verifies the eligibility of access to the results.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   */
  public function renderBlock(Request $request) {
    if ($request->isXMLHttpRequest()) {
      return new Response();
    }
    throw new AccessDeniedHttpException();
  }

}
