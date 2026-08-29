(function ($, window, Drupal, drupalSettings) {

  'use strict';

  /**
   * Installs the ajax progress override on core's ajax prototype.
   *
   * Declines while core's ajax script has not run yet, and declines again once
   * the override is in place: each of the five patches stashes the method it
   * replaces under a fixed `…Original` property and resolves that property at
   * call time, so applying them twice would make each patched method its own
   * predecessor and the next ajax request would recurse until the stack ends.
   * The first stash property still being undefined is the sentinel for all
   * five, since they are installed together or not at all. It is read off the
   * prototype rather than held in a variable here, because a flag inside this
   * chunk would not survive a second copy of the chunk in the same document
   * and the prototype does.
   */
  const initAjaxOverrides = function () {
    if (typeof Drupal.Ajax === 'undefined' || typeof Drupal.Ajax.prototype.beforeSendOriginal !== 'undefined') {
      return;
    }

    /**
     * Prepare the Ajax request before it is sent.
     *
     * @param {XMLHttpRequest} xmlhttprequest The xml httpprequest.
     * @param {object} options The options.
     * @param {object} options.extraData The option extra data.
     */
    Drupal.Ajax.prototype.beforeSendOriginal = Drupal.Ajax.prototype.beforeSend;
    Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {
      if (this.$form) {
        $('body').addClass('ajax-loading');
      }
      this.beforeSendOriginal(xmlhttprequest, options);
    };

    /**
     * Overrides the throbber progress indicator.
     */
    Drupal.Ajax.prototype.progressTimer = 0;
    Drupal.Ajax.prototype.setProgressIndicatorThrobberOriginal = Drupal.Ajax.prototype.setProgressIndicatorThrobber;
    Drupal.Ajax.prototype.setProgressIndicatorThrobber = function () {
      if (drupalSettings.neoLoader?.alwaysFullscreen !== false) {
        this.setProgressIndicatorFullscreen();
        return;
      }

      const message = this.progress.message && !drupalSettings.neoLoader?.hideAjaxMessage ? this.progress.message : null;
      const element = Drupal.behaviors.neoLoader.show(message, 'throbber', this.element);
      if (element) {
        $('body').addClass('ajax-loading');
        this.progress.element = $(element);
      }
      else {
        Drupal.Ajax.prototype.setProgressIndicatorThrobberOriginal.call(this);
      }
    };

    /**
     * Sets the fullscreen progress indicator.
     */
    Drupal.Ajax.prototype.setProgressIndicatorFullscreenOriginal = Drupal.Ajax.prototype.setProgressIndicatorFullscreen;
    Drupal.Ajax.prototype.setProgressIndicatorFullscreen = function () {
      const message = this.progress.message && !drupalSettings.neoLoader?.hideAjaxMessage ? this.progress.message : null;
      const element = Drupal.behaviors.neoLoader.show(message, 'fullscreen', 'body');
      if (element) {
        $('body').addClass('ajax-loading');
        this.progress.element = $(element);
      }
      else {
        Drupal.Ajax.prototype.setProgressIndicatorFullscreenOriginal.call(this);
      }
    };

    /**
     * Transition out.
     *
     * Teardown is handed the overlay this request built — the one stashed on
     * the ajax instance when the progress indicator was set — rather than
     * being left to find one by a document-wide lookup. With a second overlay
     * on the page the lookup can resolve to an overlay belonging to another
     * request, and tearing that one down would leave this request's own on
     * screen.
     *
     * A request that built no overlay hands over nothing, takes no overlay
     * away from anything else, and falls through to core's own success path.
     * That fallthrough is what makes the module degrade to core's behaviour on
     * a page carrying no loader markup.
     */
    Drupal.Ajax.prototype.successOriginal = Drupal.Ajax.prototype.success;
    Drupal.Ajax.prototype.success = function (response, status) {
      var _this = this;
      const overlay = this.progress && this.progress.element ? $(this.progress.element)[0] : null;
      const callback = function () {
        if (_this.progress.element) {
          _this.progress.element = null;
        }
        // var closest = $(_this.progress.element).closest('.ajax-progress-wrapper');
        // if (closest.length) {
        //   closest.removeClass('ajax-progress-wrapper');
        // }
        $('body').removeClass('ajax-loading');
        Drupal.Ajax.prototype.successOriginal.call(_this, response, status);
      }
      const element = overlay ? Drupal.behaviors.neoLoader.hide(callback, overlay) : null;
      if (!element) {
        Drupal.Ajax.prototype.successOriginal.call(this, response, status);
      }
    };
  };

  // Try once, immediately. This only succeeds in Neo dev mode, where the chunk
  // is served as a deferred ES module and so already runs after core's ajax
  // script; in a production build core's ajax script has not run yet and this
  // declines. It exists so the override does not depend on
  // Drupal.attachBehaviors ever being called.
  initAjaxOverrides();

  /**
   * Installs the ajax progress override on the first behaviour pass.
   *
   * The pass runs after every script in the document and before core's own
   * ajax behaviour has bound anything, which is the ordering this library
   * needs and cannot declare, because it is loaded before core's ajax script
   * by design.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.neoLoaderAjaxProgressOverride = {
    attach: function () {
      initAjaxOverrides();
    }
  };

})(jQuery, this, Drupal, drupalSettings);
