(function ($, window, Drupal, drupalSettings) {

  'use strict';

  // Because this script is loaded before drupal.ajax.js we need to wait for
  // it to be loaded before we can override the ajax object.
  const watchAjax = function () {
    if (typeof Drupal.Ajax !== 'undefined') {
      // If the ajax object is already defined, we can just return.
      initAjaxOverrides();
      return;
    }
    // If the ajax object is not defined, we need to wait for it to be defined.
    setTimeout(function () {
      watchAjax();
    }, 10);
  }

  const initAjaxOverrides = function () {
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
      if (drupalSettings.neoLoader.alwaysFullscreen) {
        this.setProgressIndicatorFullscreen();
        return;
      }

      const message = this.progress.message && !drupalSettings.neoLoader.hideAjaxMessage ? this.progress.message : null;
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
      const message = this.progress.message && !drupalSettings.neoLoader.hideAjaxMessage ? this.progress.message : null;
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
     */
    Drupal.Ajax.prototype.successOriginal = Drupal.Ajax.prototype.success;
    Drupal.Ajax.prototype.success = function (response, status) {
      var _this = this;
      const callback = function () {
        _this.progress.element = null;
        // var closest = $(_this.progress.element).closest('.ajax-progress-wrapper');
        // if (closest.length) {
        //   closest.removeClass('ajax-progress-wrapper');
        // }
        $('body').removeClass('ajax-loading');
        Drupal.Ajax.prototype.successOriginal.call(_this, response, status);
      }
      const element = Drupal.behaviors.neoLoader.hide(callback);
      if (!element) {
        Drupal.Ajax.prototype.successOriginal.call(this, response, status);
      }
    };
  };

  watchAjax();

})(jQuery, this, Drupal, drupalSettings);
