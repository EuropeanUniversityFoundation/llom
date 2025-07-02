<?php

namespace Drupal\llom_embed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Raw assignment controller.
 */
class EmbedController extends ControllerBase {

  /**
   * Raw assignment view.
   */
  public function assignmentRaw(NodeInterface $node) {
    $page = [
    // Note - the view mode here is `standalone_assignment`.
      'assignment' => $node->field_llom_assignment->view('standalone_assignment'),

    ];

    return $page;
  }

}
