(function(t, d, e, r) {
  const n = function() {
    if (typeof e.Ajax < "u") {
      a();
      return;
    }
    setTimeout(function() {
      n();
    }, 10);
  }, a = function() {
    e.Ajax.prototype.beforeSendOriginal = e.Ajax.prototype.beforeSend, e.Ajax.prototype.beforeSend = function(o, s) {
      this.$form && t("body").addClass("ajax-loading"), this.beforeSendOriginal(o, s);
    }, e.Ajax.prototype.progressTimer = 0, e.Ajax.prototype.setProgressIndicatorThrobberOriginal = e.Ajax.prototype.setProgressIndicatorThrobber, e.Ajax.prototype.setProgressIndicatorThrobber = function() {
      if (r.neoLoader.alwaysFullscreen) {
        this.setProgressIndicatorFullscreen();
        return;
      }
      const o = this.progress.message && !r.neoLoader.hideAjaxMessage ? this.progress.message : null, s = e.behaviors.neoLoader.show(o, "throbber", this.element);
      s ? (t("body").addClass("ajax-loading"), this.progress.element = t(s)) : e.Ajax.prototype.setProgressIndicatorThrobberOriginal.call(this);
    }, e.Ajax.prototype.setProgressIndicatorFullscreenOriginal = e.Ajax.prototype.setProgressIndicatorFullscreen, e.Ajax.prototype.setProgressIndicatorFullscreen = function() {
      const o = this.progress.message && !r.neoLoader.hideAjaxMessage ? this.progress.message : null, s = e.behaviors.neoLoader.show(o, "fullscreen", "body");
      s ? (t("body").addClass("ajax-loading"), this.progress.element = t(s)) : e.Ajax.prototype.setProgressIndicatorFullscreenOriginal.call(this);
    }, e.Ajax.prototype.successOriginal = e.Ajax.prototype.success, e.Ajax.prototype.success = function(o, s) {
      var i = this;
      const c = function() {
        i.progress.element = null, t("body").removeClass("ajax-loading"), e.Ajax.prototype.successOriginal.call(i, o, s);
      };
      e.behaviors.neoLoader.hide(c) || e.Ajax.prototype.successOriginal.call(this, o, s);
    };
  };
  n();
})(jQuery, void 0, Drupal, drupalSettings);
//# sourceMappingURL=loader-ajax.js.map
