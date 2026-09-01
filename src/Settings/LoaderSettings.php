<?php

namespace Drupal\neo_loader\Settings;

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

    // The loader gallery: every declared loader rendered at once, each one
    // selectable in place. The options are the manager's own list in the
    // manager's own order — it sorts by label already, across declared and
    // class-based loaders alike, and re-sorting here would only disagree with
    // it. The library is attached rather than inherited: it does reach this
    // page today, because core's ajax library is made to depend on the ajax
    // override which depends on it, but a form that renders loaders should not
    // be styled only because another control on it happens to bring core's
    // ajax along.
    $form['loader'] = [
      '#type' => 'radios',
      '#title' => $this->t('Throbber'),
      '#description' => $this->t('Choose your loader'),
      '#required' => TRUE,
      '#options' => $this->loaderManager->getLoaderOptionList(),
      '#default_value' => $this->getValue('loader'),
      '#attached' => [
        'library' => ['neo_loader/loader'],
      ],
    ];

    // Each tile's loader container exists to supply one thing: a colour. The
    // loader element inside it carries its own inline --loader-text, and every
    // shipped loader now paints its shapes from that, so this reaches none of
    // them. It stays for the loaders nobody here has seen: a loader plugin a
    // site declares itself may still paint from the colour it is given rather
    // than from a custom property this module happens to write, and a form
    // that renders a stranger's loader owes it a colour to read.
    //
    // The colour it inherits is the contrast colour neo_color emits for the
    // configured loader colour: that value's own token name with '-content'
    // appended, spelled here exactly as template_preprocess_neo_loader()
    // spells it. It is written as an inline declaration rather than as a
    // utility class because utilities are compiled from literals found in
    // scanned source, and a class assembled in PHP is not one.
    //
    // It goes on each tile rather than on the gallery because color inherits:
    // one declaration on the group would repaint the tile captions too, in a
    // colour chosen to be read on a near-black chip rather than on the admin
    // form's own surface.
    //
    // The padding beside it is the icon loader's chip. That loader declares
    // `padding: inherit`, so the padding of this container is the only padding
    // it can have, and a container with none leaves the one loader that cannot
    // draw its own chip standing on the tile without one. Every other loader
    // pads itself and is merely inset by this.
    $color = $this->getValue('color');
    $loader_attributes = ['class' => ['p-3']];
    if ($color) {
      $loader_attributes['style'] =
        'color: rgb(var(--color-' . $color . '-content));';
    }

    // The tile: one loader, one caption and one click target, sized the same
    // for every loader so that a row of them reads as a row. Every rule that
    // shapes it is written out here as a Tailwind utility literal, because
    // utilities are compiled from the literals a scan finds in its source and
    // both themes scan this file. Nothing is added to the module's own
    // stylesheet, which ships on every page of every installing site to style
    // a form only an administrator ever opens.
    //
    // Tiles are inline blocks rather than grid cells because the element that
    // would carry the grid — the wrapper the radios theme wrapper renders —
    // takes no classes from this plugin. Equal boxes flowing into rows wrap on
    // their own when the viewport narrows, which is the behaviour a grid would
    // have had to be told.
    $tile = [
      'relative',
      'inline-block',
      'align-top',
      'm-1',
      // The theme lays radio options out as a stacked list and trims the
      // outermost margins of that stack. Tiles are a wrapping row instead, and
      // a first tile 4px shallower than the one beside it is a row that does
      // not line up, so both trims are put back.
      'first:mt-1',
      'last:mb-1',
      'h-40',
      'w-40',
      'rounded-md',
      'border',
      'border-base-200',
      'p-2',
      'transition-colors',
      'hover:border-base-400',
      // The current choice is drawn from the checked state of the tile's own
      // input, so showing it involves no JavaScript at all.
      'has-[:checked]:border-primary',
      'has-[:checked]:bg-primary/10',
      'has-[:checked]:ring-2',
      'has-[:checked]:ring-primary',
    ];

    // The three parts of a tile are all taken out of flow, so that the tile is
    // the size declared above rather than the sum of whatever the form element
    // template wraps around each of them. The chip is pinned to the top and
    // centred; the label is stretched over the whole tile, holding its caption
    // at the bottom, which is what makes a pointer click anywhere on the tile
    // select that loader while the accessible name stays the loader's own
    // label; and the radio sits in the corner above the label, where it keeps
    // its own focus ring and stays directly clickable.
    //
    // The font size is on the chip's outer container rather than on the one
    // carrying the colour, because that inner container is the loader's own
    // parent and may declare nothing but the colour it exists to pass down.
    // The icon loader takes its size from whatever encloses it, and the theme
    // sizes a field prefix for a line of text rather than for a throbber, so
    // without this the one loader drawn from a font is a fraction of the size
    // of the eleven drawn from boxes.
    $chip = [
      'absolute',
      'inset-x-0',
      'top-0',
      'flex',
      'justify-center',
      'text-3xl',
    ];
    $caption = [
      'absolute',
      'inset-0',
      'flex',
      'items-end',
      'justify-center',
      // Balances the padding the theme's own radio label carries on the other
      // side, so that a centred caption is actually centred.
      'pr-1.5',
      'text-center',
      'leading-tight',
    ];
    $radio = [
      'absolute',
      'left-0',
      'top-0',
      'z-10',
    ];

    // The rendered loader is the option's own field prefix, so that it belongs
    // to that option's form element rather than to separate markup the form
    // would have to keep aligned with it. Radios::processRadios() fills in
    // everything else each option needs and leaves what is declared here
    // alone — including the option's own attributes, which it would otherwise
    // copy from the group onto every radio input.
    foreach (array_keys($form['loader']['#options']) as $id) {
      $form['loader'][$id]['#wrapper_attributes'] = ['class' => $tile];
      $form['loader'][$id]['#label_attributes'] = ['class' => $caption];
      $form['loader'][$id]['#attributes'] = ['class' => $radio];
      $form['loader'][$id]['#field_prefix'] = [
        '#type' => 'container',
        '#attributes' => ['class' => $chip],
        'color' => [
          '#type' => 'container',
          '#attributes' => $loader_attributes,
          'loader' => [
            '#theme' => 'neo_loader',
            '#loader' => (string) $id,
            '#title' => '',
          ],
        ],
      ];
    }

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
        // The one signal that crosses from this control to the ajax progress
        // override, which reads it off the element that triggered the request
        // and holds that overlay on screen instead of tearing it down when the
        // response lands. Nothing else in the form carries it, so no other
        // request is affected. It is deliberately not a documented affordance:
        // the spelling matches the data-neo-loader-message / -type / -delay
        // family as a convention only, and unlike those three it is read off
        // the ajax instance rather than by the loader behaviour's click path.
        'data-neo-loader-hold' => TRUE,
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
