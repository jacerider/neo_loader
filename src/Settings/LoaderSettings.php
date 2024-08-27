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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Module settings.
 *
 * @Settings(
 *   id = "neo_loader",
 *   label = @Translation("Neo Loader"),
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
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MessengerInterface $messenger,
    FormBuilderInterface $form_builder,
    LoaderManagerInterface $loader_manager,
    RequestStack $request_stack,
    AdminContext $admin_context
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger, $form_builder);
    $this->loaderManager = $loader_manager;
    $this->request = $request_stack->getCurrentRequest();
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
      $container->get('request_stack'),
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
      '#title' => t('Throbber'),
      '#description' => t('Choose your loader'),
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

    $loader_id = $this->getValue('loader');
    $form['wrapper']['preview'] = [
      '#theme' => 'neo_loader',
      '#loader' => $loader_id,
      '#title' => '',
    ];

    $form['hide_ajax_message'] = [
      '#type' => 'checkbox',
      '#title' => t('Never show ajax loading message'),
      '#description' => t('Choose whether you want to hide the loading ajax message even when it is set.'),
      '#default_value' => $this->getValue('hide_ajax_message'),
    ];

    $form['always_fullscreen'] = [
      '#type' => 'checkbox',
      '#title' => t('Always show loader as overlay (fullscreen)'),
      '#description' => t('Choose whether you want to show the loader as an overlay, no matter what the settings of the loader are.'),
      '#default_value' => $this->getValue('always_fullscreen'),
    ];

    $form['show_admin_paths'] = [
      '#type' => 'checkbox',
      '#title' => t('Use ajax loader on admin pages'),
      '#description' => t('Choose whether you also want to show the loader on admin pages or still like to use the default core loader.'),
      '#default_value' => $this->getValue('show_admin_paths'),
    ];

    $form['loader_position'] = [
      '#type' => 'textfield',
      '#title' => t('Loader position'),
      '#required' => TRUE,
      '#description' => t('Allows you to change the position where the loader is inserted. A valid css selector must be used here. The default value is: body'),
      '#default_value' => $this->getValue('loader_position'),
    ];

    $form['color'] = [
      '#type' => 'neo_color',
      '#title' => $this->t('Color'),
      '#description' => $this->t('The default color of the loader.'),
      '#required' => TRUE,
      '#default_value' => $this->getValue('color'),
    ];

    return $form;
  }

  /**
   * Ajax callback when loader is changed.
   */
  public static function ajaxLoaderChange(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * {@inheritdoc}
   */
  public function routeIsApplicable() {
    $is_applicable = FALSE;
    $is_admin_route = $this->adminContext->isAdminRoute();
    $current_route_name = $this->request->attributes->get('_route');

    if (!$is_admin_route) {
      // Always applicable.
      $is_applicable = TRUE;
    }
    elseif ($this->getValue('show_admin_paths') && $current_route_name != 'neo.loader') {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

}
