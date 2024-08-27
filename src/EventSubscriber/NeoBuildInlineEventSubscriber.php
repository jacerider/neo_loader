<?php

declare(strict_types = 1);

namespace Drupal\neo_loader\EventSubscriber;

use Drupal\neo_build\Event\NeoBuildInlineEvent;
use Drupal\neo_settings\SettingsRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Act on build events.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class NeoBuildInlineEventSubscriber implements EventSubscriberInterface {

  /**
   * The settings.
   *
   * @var \Drupal\neo_settings\Plugin\SettingsInterface
   */
  protected $settings;

  /**
   * Constructs a new NeoBuildEventSubscriber object.
   */
  public function __construct(
    SettingsRepositoryInterface $settings
  ) {
    $this->settings = $settings->getActive();
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * We inject the CSS variables directly into the DOM so that we do not need
   * to wait for the build to complete before the CSS is applied.
   *
   * @param \Drupal\neo_build\Event\NeoBuildInlineEvent $event
   *   The neo build dev event.
   */
  public function onInlineBuild(NeoBuildInlineEvent $event) {
    if ($color = $this->settings->getValue('color')) {
      $event->addCssValue('--loader-bg', 'var(--color-' . $color . ')');
      $event->addCssValue('--loader-text', 'var(--color-' . str_replace('-', '-content-', $color) . ')');
      $event->addCacheTags(['config:neo_loader.settings']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      NeoBuildInlineEvent::EVENT_NAME => 'onInlineBuild',
    ];
  }

}
