(function(n) {
  n.behaviors.neoLoaderAjax = {
    attach: (t) => {
      once("neo-loader-ajax", "[data-loader-url]").forEach((o) => {
        var r = new IntersectionObserver((i, a) => {
          i.forEach((s) => {
            if (s.intersectionRatio > 0) {
              a.disconnect();
              let e = o.dataset.loaderUrl;
              if (e) {
                e = d(e);
                const h = {
                  url: e,
                  progress: !1,
                  wrapper: o.id,
                  effect: "fade",
                  httpMethod: "GET"
                }, c = n.ajax(h);
                c.error = function(p, g, f) {
                  if (console.error("Error loading content:", f), this.progress.element && $(this.progress.element).remove(), this.progress.object && this.progress.object.stopMonitoring(), $(this.wrapper).show(), $(this.element).prop("disabled", !1), this.$form && document.body.contains(this.$form.get(0))) {
                    const l = this.settings || drupalSettings;
                    n.attachBehaviors(this.$form.get(0), l);
                  }
                }, c.execute();
              }
            }
          });
        });
        r.observe(o);
      });
    }
  };
  const d = (t) => {
    const o = t.split("?");
    let r = null;
    if (o[1] && (r = new URLSearchParams("?" + o[1]).get("destination")), !r) {
      let s = "destination", e = window.location.pathname;
      s = encodeURI(s), e = encodeURI(e);
      var i = t && t.indexOf("?") !== -1, a = i ? "&" : "?";
      t += a + s + "=" + e;
    }
    return t;
  };
})(Drupal);
//# sourceMappingURL=ajax.js.map
