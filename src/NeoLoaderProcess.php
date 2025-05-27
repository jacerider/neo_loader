<?php

namespace Drupal\neo_loader;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements trusted prerender callbacks for the Neo.
 *
 * @internal
 */
class NeoLoaderProcess {

  /**
   * Process callback for details elements.
   */
  public static function input(array $element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#loader'])) {
      $element['#attached']['library'][] = 'neo_loader/loader';
      $element['#attributes']['class'][] = 'use-neo-loader';
      if (is_string($element['#loader']) || $element['#loader'] instanceof MarkupInterface) {
        $element['#attributes']['data-neo-loader-message'] = $element['#loader'];
      }
    }
    return $element;
  }

}
