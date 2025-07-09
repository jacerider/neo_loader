<?php

namespace Drupal\neo_loader;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted prerender callbacks for Neo Loader.
 *
 * @internal
 */
class NeoLoaderPreRender implements TrustedCallbackInterface {

  /**
   * Prerender callback for elements that support loader.
   */
  public static function loader($element) {
    if (!empty($element['#loader'])) {
      $element['#attached']['library'][] = 'neo_loader/loader';
      $element['#attributes']['class'][] = 'use-neo-loader';
      if (is_string($element['#loader']) || $element['#loader'] instanceof MarkupInterface) {
        $element['#attributes']['data-neo-loader-message'] = $element['#loader'];
      }
    }
    return $element;
  }

  /**
   * Prerender callback for elements that support autosubmit.
   */
  public static function autosubmit($element) {
    if (!empty($element['#autosubmit'])) {
      $element['#attached']['library'][] = 'neo_loader/autosubmit';
      $element['#attributes']['class'][] = 'use-neo-autosubmit';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'loader',
      'autosubmit',
    ];
  }

}
