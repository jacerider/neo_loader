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

    // The loader test: one control per loader presentation, keyed by the
    // presentation it produces. Comparing presentations is the same problem as
    // comparing loaders, and a mode selector on a single button would make it
    // serial in exactly the way the gallery above has stopped being. Two
    // buttons put either presentation one click away instead, whatever the
    // always_fullscreen setting says.
    //
    // The pair carries one description naming the presentation the current
    // always_fullscreen value produces, because two equal buttons otherwise
    // leave the setting's own effect unstated: both work whatever it says, so
    // nothing on the form would tell a reader which of the two tests is the
    // live one. It sits on the pair rather than on a control, where it would
    // read as that control's own description.
    $form['test'] = [
      '#type' => 'item',
      '#description' => $this->getValue('always_fullscreen')
        ? $this->t('Ajax requests currently produce the fullscreen overlay, so the overlay test is the live one. Both tests work whatever the overlay setting says.')
        : $this->t('Ajax requests currently produce the inline throbber, so the inline test is the live one. Both tests work whatever the overlay setting says.'),
    ];

    $form['test']['fullscreen'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test fullscreen overlay'),
      // Naming the empty handler here is what replaces the settings form's own
      // submit handlers for this click: core runs the triggering element's
      // handlers when it has any and the form's own only when it has none. A
      // control that omits it is a save button, which is the whole distance
      // between testing a presentation and writing always_fullscreen.
      '#submit' => [[__CLASS__, 'submitLoaderSubmit']],
      '#limit_validation_errors' => [],
      '#id' => 'neo-loader-test-fullscreen',
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
        'wrapper' => 'neo-loader-test-fullscreen',
        // The whole of this control's mechanism. Core turns a declared
        // progress type into a call to the matching setProgressIndicator*
        // method, so 'fullscreen' is dispatched straight to the overridden
        // fullscreen path -- a path that never consults always_fullscreen.
        // That is why the overlay is reachable on a site that has turned the
        // setting off, with no new mechanism on this side at all.
        'progress' => ['type' => 'fullscreen'],
      ],
    ];

    $form['test']['throbber'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test inline throbber'),
      // The same handler and the same reason as the control above: naming it
      // is what stops this click saving the form.
      '#submit' => [[__CLASS__, 'submitLoaderSubmit']],
      '#limit_validation_errors' => [],
      '#id' => 'neo-loader-test-throbber',
      '#attributes' => [
        'class' => ['btn btn-xs'],
        // The hold, on this control for the reason it is on the one above: a
        // test that is not held is not readable, in either presentation.
        'data-neo-loader-hold' => TRUE,
        // The other half of this control's mechanism, and the only new signal
        // this pair introduces: the ajax progress override reads it off the
        // triggering element -- exactly where and how it reads the hold -- and
        // skips its redirect to the fullscreen path. Without it the throbber
        // path is unreachable on a site running the shipped default, which is
        // the whole reason this control exists.
        //
        // It rides on the element rather than in the ajax options because ADR
        // 0005 settled that question for the hold: an options-borne signal is
        // invisible to anything inspecting the page and matches none of the
        // data-neo-loader-* vocabulary already read off elements. Its values
        // are the presentation names show() already takes rather than a third
        // spelling of the same two things. Like the hold it is internal to
        // this control, not a surface a site may use.
        'data-neo-loader-presentation' => 'throbber',
      ],
      '#ajax' => [
        'callback' => [__CLASS__, 'ajaxLoaderTest'],
        'wrapper' => 'neo-loader-test-throbber',
        // Half of this control's mechanism: it reaches the overridden throbber
        // path rather than being left to whatever core's default type would
        // resolve to. That path does consult always_fullscreen, so the other
        // half is the presentation pin in this control's attributes.
        'progress' => ['type' => 'throbber'],
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for the loader test controls.
   *
   * The body must stay empty and the handler must stay declared. Naming it in
   * each control's '#submit' array is what replaces the settings form's own
   * submit handlers for that click, so an empty body is the only thing
   * stopping a test of a presentation from saving the form. Give this method
   * work to do, or delete it and let the form's own handlers run, and either
   * control becomes a save button.
   */
  public static function submitLoaderSubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax handler for the loader test controls.
   *
   * The control that was clicked, resolved from the form state, rather than a
   * fixed key path: there are two controls now, and a fixed path would answer
   * for the wrong one half the time -- replacing the button the reader did not
   * press with the response to the one they did.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state, which carries the element that triggered the request.
   *
   * @return array
   *   The clicked control.
   */
  public static function ajaxLoaderTest(array &$form, FormStateInterface $form_state) {
    return NestedArray::getValue(
      $form,
      $form_state->getTriggeringElement()['#array_parents'],
    );
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
