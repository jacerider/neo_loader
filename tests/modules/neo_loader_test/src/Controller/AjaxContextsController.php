<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\neo_loader_test\AjaxContextsTrait;
use Drupal\neo_loader_test\Form\AjaxContextsForm;
use Drupal\neo_settings\SettingsRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Puts the five shapes core produces around an ajax trigger on one page.
 *
 * The inline throbber is placed relative to the thing that triggered the
 * request, so every claim about it is a claim about a context: a form item it
 * covers, a line of text it joins, a wrapper core told it to stand beside.
 * None of the five sit together on any screen a site actually ships, and
 * hunting them one at a time across a views listing and a node form is what
 * this page ends.
 *
 * The page is in two halves, and the split is load-bearing rather than
 * tidiness. The inline throbber covers the closest `.js-form-item, form` its
 * trigger has, and stands in the flow only when it has neither; a link
 * rendered inside this page's form would therefore cover the form and
 * demonstrate the covering case a second time instead of the two cases it is
 * here for. So the three contexts a form element produces are in the form,
 * and the two a link produces are outside it.
 *
 * It is a fixture and stays one: no config, no menu link, nothing left behind
 * when the module is uninstalled.
 */
final class AjaxContextsController extends ControllerBase {

  use AjaxContextsTrait;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\neo_settings\SettingsRepositoryInterface $loaderSettings
   *   The loader settings repository, read so the page can say out loud
   *   whether the setting the check depends on is where the check needs it.
   */
  public function __construct(
    protected SettingsRepositoryInterface $loaderSettings,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('neo_loader.settings')
    );
  }

  /**
   * Builds the fixture page.
   *
   * @return array
   *   The page render array.
   */
  public function page(): array {
    $build = [
      '#attributes' => ['class' => ['neo-loader-test-ajax-contexts']],
      // The page reports a setting, so it is never served from a cache that
      // outlives a change to it.
      '#cache' => ['max-age' => 0],
      // The two link contexts are ajax links rather than form elements, so
      // the library that binds them is asked for here rather than inherited
      // from the elements that carry #ajax.
      '#attached' => ['library' => ['core/drupal.ajax']],
    ];

    $build['intro'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="neo-loader-test-intro"><p>{{ subject }}</p><p>{{ setting }}</p><p>{{ state }}</p><p>{{ delay }}</p></div>',
      '#context' => [
        'subject' => $this->t('This page demonstrates the inline throbber: the loader placed beside or over the element whose request is in flight, rather than over the whole page. Each of the five triggers below fires a real ajax request, and the throbber each one produces is the subject of the check.'),
        'setting' => $this->t('It needs "Always show loader as overlay" switched off, on the loader settings page at /admin/config/neo/neo-loader. With that setting on, every trigger below shows the fullscreen overlay instead and this page measures nothing.'),
        'state' => $this->settingState(),
        'delay' => $this->t('Every request here is held open for @seconds seconds on purpose, so that the throbber can be looked at and measured rather than glimpsed.', [
          '@seconds' => self::DELAY_SECONDS,
        ]),
      ],
    ];

    $build['status'] = [
      '#type' => 'inline_template',
      '#template' => '<p class="neo-loader-test-status"><strong>{{ label }}</strong> <span id="{{ id }}">{{ initial }}</span></p>',
      '#context' => [
        'label' => $this->t('Last request:'),
        'id' => ltrim(self::STATUS_SELECTOR, '#'),
        'initial' => $this->t('none yet.'),
      ],
    ];

    // The three contexts whose trigger is a form element.
    $build['form'] = $this->formBuilder()->getForm(AjaxContextsForm::class);

    // A dropbutton, which is core's own markup and carries
    // data-drupal-ajax-container on its wrapper. It is rendered as a
    // dropbutton element rather than imitated, because the attribute being
    // core's rather than the fixture's is the whole point of the context.
    $build['dropbutton_context'] = $this->ajaxContextWrapper('dropbutton', $this->t('An #ajax link in a dropbutton. Core ships data-drupal-ajax-container on the dropbutton wrapper, which is core asking for the throbber to stand beside the wrapper rather than land inside the list.'));
    $build['dropbutton_context']['dropbutton'] = [
      '#type' => 'dropbutton',
      '#links' => [
        'run' => [
          'title' => $this->t('Send a request'),
          'url' => Url::fromRoute('neo_loader_test.ajax_context_respond', ['context' => 'dropbutton']),
          'attributes' => ['class' => ['use-ajax']],
        ],
        'run_all' => [
          'title' => $this->t('Send another'),
          'url' => Url::fromRoute('neo_loader_test.ajax_context_respond', ['context' => 'dropbutton']),
          'attributes' => ['class' => ['use-ajax']],
        ],
      ],
    ];

    // A link in a paragraph of running text: the line-box case. The paragraph
    // is long enough to wrap, so a throbber that changes the line it joins
    // moves the lines under it as well.
    $build['text_context'] = $this->ajaxContextWrapper('text', $this->t('An #ajax link in running copy. The line holding the throbber has to measure the same height as the lines around it.'));
    $build['text_context']['paragraph'] = [
      '#type' => 'inline_template',
      '#template' => '<p class="neo-loader-test-copy">{{ before }} {{ link }} {{ after }}</p>',
      '#context' => [
        'before' => $this->t('An ajax link in a paragraph has to sit on its line like any other word on it. This paragraph is deliberately long enough to wrap, so that when you'),
        'link' => [
          '#type' => 'link',
          '#title' => $this->t('send a request from here'),
          '#url' => Url::fromRoute('neo_loader_test.ajax_context_respond', ['context' => 'text']),
          '#attributes' => ['class' => ['use-ajax']],
        ],
        'after' => $this->t('the throbber lands mid-paragraph, and every line under it has somewhere to move to if the throbber changes the height of the line it joined.'),
      ],
    ];

    return $build;
  }

  /**
   * Answers the two contexts whose trigger is a link.
   *
   * A link is not a form element, so its request cannot be answered by a form
   * callback; the response itself is the one every context shares.
   *
   * @param string $context
   *   The context name, taken from the path.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The shared context response.
   */
  public function respond(string $context): AjaxResponse {
    return $this->ajaxContextResponse($context);
  }

  /**
   * Says whether the setting the page depends on is where it needs to be.
   *
   * A reader who sees a fullscreen overlay instead of an inline throbber has
   * one likely reason and no way to see it from the page, since the setting
   * lives on a screen of its own.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A sentence naming the current state of "Always show loader as overlay".
   */
  protected function settingState() {
    if ($this->loaderSettings->getActive()->getValue('always_fullscreen')) {
      return $this->t('Right now that setting is ON, so what you are about to see is the fullscreen overlay and not the subject of this page.');
    }
    return $this->t('Right now that setting is OFF, which is what this page needs.');
  }

}
