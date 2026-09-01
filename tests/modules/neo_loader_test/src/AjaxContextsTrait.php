<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * The parts the fixture page's two halves share.
 *
 * The five contexts differ in where their trigger sits and in nothing else,
 * so the response and the wrapper around each context are written once and
 * shared by the controller that owns the page and the form that owns the
 * three contexts a form element can produce. A response that varied per
 * context would be a second variable in a check whose whole subject is
 * placement and size.
 */
trait AjaxContextsTrait {

  /**
   * The selector of the status region every response writes into.
   *
   * One region for all five, placed away from the triggers: a response that
   * wrote next to its own trigger would move the thing being measured.
   */
  protected const STATUS_SELECTOR = '#neo-loader-test-status';

  /**
   * How long a fixture request is deliberately held open, in seconds.
   *
   * An ajax request answered by the same machine completes in a few hundred
   * milliseconds, which is long enough for the throbber to appear and far too
   * short to look at or to measure. The wait is most of the reason this page
   * exists, so it is stated once here rather than tuned per context.
   */
  protected const DELAY_SECONDS = 2;

  /**
   * The human label for each of the five contexts, keyed by context name.
   *
   * @return array
   *   Context name => label, in the order the page renders them: the three a
   *   form element produces first, then the two a link produces.
   */
  protected function ajaxContextLabels(): array {
    return [
      'select' => $this->t('Select in a form item'),
      'checkbox' => $this->t('Checkbox in a form item'),
      'table' => $this->t('Trigger in a table cell'),
      'dropbutton' => $this->t('Link in a dropbutton'),
      'text' => $this->t('Link in running text'),
    ];
  }

  /**
   * Wraps one context in a heading and a sentence saying what it shows.
   *
   * @param string $context
   *   The context name, which names the heading and marks the wrapper.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $note
   *   What this context demonstrates, in one sentence.
   *
   * @return array
   *   A container the caller appends its trigger to.
   */
  protected function ajaxContextWrapper(string $context, $note): array {
    $labels = $this->ajaxContextLabels();
    // The five contexts have to be told apart at a glance, and this page
    // ships no stylesheet: a fixture that needed one would need the asset
    // build run before it could be looked at. So the separation is written as
    // inline style, on a plain container.
    //
    // A container, specifically. A fieldset or a details element would draw
    // the box for free, but a themed site puts `js-form-item` on the wrapper
    // around either, and `js-form-item` is one of the two ancestors the
    // inline throbber covers -- which would turn the two link contexts into a
    // second copy of the covering case and delete the two cases they are here
    // for.
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['neo-loader-test-context'],
        'data-neo-loader-test-context' => $context,
        'style' => 'border: 1px solid #ccc; padding: 1rem; margin-bottom: 1rem;',
      ],
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $labels[$context] ?? $context,
        '#attributes' => [
          'style' => 'font-size: 1.125rem; font-weight: 700; margin: 0;',
        ],
      ],
      'note' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $note,
        '#attributes' => ['style' => 'margin: 0 0 0.75rem;'],
      ],
    ];
  }

  /**
   * Answers one context, slowly enough that its throbber can be looked at.
   *
   * @param string $context
   *   The context name, as the page named it on its trigger.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response that rewrites the status region and nothing else.
   */
  protected function ajaxContextResponse(string $context): AjaxResponse {
    sleep(self::DELAY_SECONDS);
    $labels = $this->ajaxContextLabels();
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(self::STATUS_SELECTOR, [
      '#markup' => $this->t('@label, completed at @time on the server clock.', [
        '@label' => $labels[$context] ?? $context,
        // The clock is read for one reason only: clicking the same trigger
        // twice has to change something on screen, or the second request
        // looks like a request that never fired.
        '@time' => date('H:i:s'),
      ]),
    ]));
    return $response;
  }

}
