<?php

declare(strict_types=1);

namespace Drupal\neo_loader_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\neo_loader_test\AjaxContextsTrait;

/**
 * The three ajax contexts on the fixture page a form element produces.
 *
 * The other two are links and are rendered outside this form on purpose --
 * see the controller, which owns the page and says why.
 *
 * Nothing here is saved: every trigger answers over ajax, and the fixture
 * holds no state between requests.
 */
final class AjaxContextsForm extends FormBase {

  use AjaxContextsTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'neo_loader_test_ajax_contexts';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // A select inside a .js-form-item: the covering case, on a form item tall
    // enough to hold what covers it.
    $form['select_context'] = $this->ajaxContextWrapper('select', $this->t('An #ajax select. The badge covers the form item this select sits in.'));
    $form['select_context']['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a value'),
      '#options' => [
        'first' => $this->t('First'),
        'second' => $this->t('Second'),
        'third' => $this->t('Third'),
      ],
      '#empty_option' => $this->t('- Choose -'),
      '#ajax' => [
        'callback' => '::respond',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
      '#neo_loader_test_context' => 'select',
    ];

    // A checkbox inside a .js-form-item: the same covering case, on the
    // shortest form item core produces.
    $form['checkbox_context'] = $this->ajaxContextWrapper('checkbox', $this->t('An #ajax checkbox. The same covering badge, on a form item one line high.'));
    $form['checkbox_context']['checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tick to send a request'),
      '#ajax' => [
        'callback' => '::respond',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
      '#neo_loader_test_context' => 'checkbox',
    ];

    // A trigger inside a table cell. Core carries a
    // `tr .ajax-progress-throbber .throbber` rule, so it treats a table row
    // as its own context and so does this page.
    $form['table_context'] = $this->ajaxContextWrapper('table', $this->t('An #ajax button in a table cell. Core styles a throbber in a table row differently from one anywhere else.'));
    $form['table_context']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Row'), $this->t('Operations')],
    ];
    $form['table_context']['table']['row'] = [
      'label' => ['#markup' => $this->t('The one row this table needs')],
      'operations' => [
        '#type' => 'submit',
        '#name' => 'neo_loader_test_table_trigger',
        '#value' => $this->t('Send a request'),
        // The button answers over ajax and changes nothing on the server, so
        // it has no business validating the two controls above it.
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::respond',
          'progress' => ['type' => 'throbber'],
        ],
        '#neo_loader_test_context' => 'table',
      ],
    ];

    return $form;
  }

  /**
   * Answers the three contexts whose trigger is a form element.
   *
   * One callback for all three: which context asked is written on the
   * trigger, so the answer does not need three methods differing by a string.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state, whose triggering element names the context.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The shared context response.
   */
  public function respond(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return $this->ajaxContextResponse($trigger['#neo_loader_test_context'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
