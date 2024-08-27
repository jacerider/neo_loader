(function(r, c, e, t) {
  e.Ajax.prototype.beforeSendOriginal = e.Ajax.prototype.beforeSend, e.Ajax.prototype.beforeSend = function(o, s) {
    this.$form && r("body").addClass("ajax-loading"), this.beforeSendOriginal(o, s);
  }, e.Ajax.prototype.progressTimer = 0, e.Ajax.prototype.setProgressIndicatorThrobberOriginal = e.Ajax.prototype.setProgressIndicatorThrobber, e.Ajax.prototype.setProgressIndicatorThrobber = function() {
    if (t.neoLoader.alwaysFullscreen) {
      this.setProgressIndicatorFullscreen();
      return;
    }
    const o = this.progress.message && !t.neoLoader.hideAjaxMessage ? this.progress.message : null, s = e.behaviors.neoLoader.show(o, "throbber", this.element, 300);
    s ? (r("body").addClass("ajax-loading"), this.progress.element = r(s)) : e.Ajax.prototype.setProgressIndicatorThrobberOriginal.call(this);
  }, e.Ajax.prototype.setProgressIndicatorFullscreenOriginal = e.Ajax.prototype.setProgressIndicatorFullscreen, e.Ajax.prototype.setProgressIndicatorFullscreen = function() {
    const o = this.progress.message && !t.neoLoader.hideAjaxMessage ? this.progress.message : null, s = e.behaviors.neoLoader.show(o, "fullscreen", "body", 300);
    s ? (r("body").addClass("ajax-loading"), this.progress.element = r(s)) : e.Ajax.prototype.setProgressIndicatorFullscreenOriginal.call(this);
  }, e.Ajax.prototype.successOriginal = e.Ajax.prototype.success, e.Ajax.prototype.success = function(o, s) {
    var a = this;
    const i = function() {
      a.progress.element = null;
      var n = r(a.progress.element).closest(".ajax-progress-wrapper");
      n.length && n.removeClass("ajax-progress-wrapper"), r("body").removeClass("ajax-loading"), e.Ajax.prototype.successOriginal.call(a, o, s);
    };
    e.behaviors.neoLoader.hide(i) || e.Ajax.prototype.successOriginal.call(this, o, s);
  };
})(jQuery, globalThis, Drupal, drupalSettings);
//# sourceMappingURL=loader.ajax.js.map
