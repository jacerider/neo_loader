<?php

namespace Drupal\neo_loader\Settings;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\neo_loader\LoaderManagerInterface;
use Drupal\neo_settings\Plugin\SettingsBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module settings.
 *
 * @Settings(
 *   id = "neo_loader",
 *   label = @Translation("Loader"),
 *   config_name = "neo_loader.settings",
 *   menu_title = @Translation("Loader"),
 *   route = "/admin/config/neo/neo-loader",
 *   admin_permission = "administer neo_loader",
 *   variation_allow = false,
 *   variation_conditions = false,
 *   variation_ordering = false,
 * )
 */
final class LoaderSettings extends SettingsBase {

  /**
   * The loader manager.
   *
   * @var \Drupal\neo_loader\LoaderManagerInterface
   */
  protected $loaderManager;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * {@inheritdoc}
   *
   * The loader manager and the admin context are required rather than
   * optional, which phpstan's consistent-constructor rule objects to. They are
   * required because nothing is optional about them: `create()` passes both
   * every time, the settings manager builds every settings plugin through the
   * container factory, and this class is final, so no subclass can arrive with
   * a different constructor. Spelling them optional only moved a type error
   * that named the parameter into a null dereference that named nothing.
   *
   * @phpstan-ignore parameter.notOptional, parameter.notOptional
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MessengerInterface $messenger,
    FormBuilderInterface $form_builder,
    LoaderManagerInterface $loader_manager,
    AdminContext $admin_context,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger, $form_builder);
    $this->loaderManager = $loader_manager;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('form_builder'),
      $container->get('plugin.manager.neo_loader'),
      $container->get('router.admin_context')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Instance settings are settings that are set both in the base form and the
   * variation form. They are editable in both forms and the values are merged
   * together.
   */
  protected function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['wrapper'] = [
      '#prefix' => '<div id="loader-wrapper">',
      '#suffix' => '</div>',
      '#parents' => $form['#parents'],
    ];

    $form['wrapper']['loader'] = [
      '#type' => 'select',
      '#title' => $this->t('Throbber'),
      '#description' => $this->t('Choose your loader'),
      '#required' => TRUE,
      '#options' => $this->loaderManager->getLoaderOptionList(),
      '#default_value' => $this->getValue('loader'),
      '#ajax' => [
        'wrapper' => 'loader-wrapper',
        'callback' => [__CLASS__, 'ajaxLoaderChange'],
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Switching loader...'),
        ],
      ],
    ];

    $parts = explode('-', $this->getValue('color'));
    $last = array_pop($parts);
    $parts[] = 'content';
    $loader_id = $this->getValue('loader');
    $form['wrapper']['preview'] = [
      '#theme' => 'neo_loader',
      '#loader' => $loader_id,
      '#title' => '',
      '#prefix' => '<div class="text-' . implode('-', $parts) . '-' . $last . '">',
      '#suffix' => '</div>',
    ];

    $form['hide_ajax_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Never show ajax loading message'),
      '#description' => $this->t('Choose whether you want to hide the loading ajax message even when it is set.'),
      '#default_value' => $this->getValue('hide_ajax_message'),
    ];

    $form['always_fullscreen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show loader as overlay (fullscreen)'),
      '#description' => $this->t('Choose whether you want to show the loader as an overlay, no matter what the settings of the loader are.'),
      '#default_value' => $this->getValue('always_fullscreen'),
    ];

    $form['show_admin_paths'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use ajax loader on admin pages'),
      '#description' => $this->t('Choose whether you also want to show the loader on admin pages or still like to use the default core loader.'),
      '#default_value' => $this->getValue('show_admin_paths'),
    ];

    $form['loader_position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loader position'),
      '#required' => TRUE,
      '#description' => $this->t('Allows you to change the position where the loader is inserted. A valid css selector must be used here. The default value is: body'),
      '#default_value' => $this->getValue('loader_position'),
    ];

    $form['color'] = [
      '#type' => 'neo_color',
      '#title' => $this->t('Color'),
      '#description' => $this->t('The default color of the loader.'),
      '#required' => TRUE,
      '#default_value' => $this->getValue('color'),
    ];

    $form['test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test loader'),
      '#submit' => [[__CLASS__, 'submitLoaderSubmit']],
      '#limit_validation_errors' => [],
      '#id' => 'neo-loader-test',
      '#attributes' => [
        'class' => ['btn btn-xs'],
      ],
      '#ajax' => [
        'callback' => [__CLASS__, 'ajaxLoaderTest'],
        'wrapper' => 'neo-loader-test',
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for the loader test button.
   *
   * The body must stay empty and the handler must stay declared. Naming it in
   * the button's '#submit' array is what replaces the settings form's own
   * submit handlers for that click, so an empty body is the only thing
   * stopping a test of the loader from saving the form. Give this method work
   * to do, or delete it and let the form's own handlers run, and "Test loader"
   * becomes a save button.
   */
  public static function submitLoaderSubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax handler for the test loader button.
   */
  public static function ajaxLoaderTest(array &$form, FormStateInterface $form_state) {
    return $form['instance']['test'];
  }

  /**
   * Ajax callback when loader is changed.
   */
  public static function ajaxLoaderChange(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Determines whether the current route gets the loader attached to the page.
   *
   * No route is exempt, the loader settings page included: the loader test on
   * that page shows the active loader through this very path, so exempting the
   * page it lives on would leave it showing nothing. See ADR 0002. With no
   * route object at all the admin context answers "not an admin route", so a
   * request-less context — CLI, container warm-up — is applicable.
   *
   * @return bool
   *   TRUE on every non-admin route, and on an admin route only when the
   *   admin-paths setting is on. FALSE otherwise.
   */
  public function routeIsApplicable() {
    if (!$this->adminContext->isAdminRoute()) {
      return TRUE;
    }
    return (bool) $this->getValue('show_admin_paths');
  }

}
