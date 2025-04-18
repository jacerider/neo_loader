(function(s) {
  s.behaviors.neoLoaderAjax = {
    attach: (a) => {
      once("neo-loader-ajax", "[data-loader-url]").forEach((t) => {
        var n = new IntersectionObserver((r, i) => {
          r.forEach((o) => {
            if (o.intersectionRatio > 0) {
              i.disconnect();
              let e = t.dataset.loaderUrl;
              if (e) {
                e = c(e);
                const d = {
                  url: e,
                  progress: !1,
                  wrapper: t.id,
                  effect: "fade",
                  httpMethod: "GET"
                };
                s.ajax(d).execute();
              }
            }
          });
        });
        n.observe(t);
      });
    }
  };
  const c = (a) => {
    const t = a.split("?");
    let n = null;
    if (t[1] && (n = new URLSearchParams("?" + t[1]).get("destination")), !n) {
      let o = "destination", e = window.location.pathname;
      o = encodeURI(o), e = encodeURI(e);
      var r = a && a.indexOf("?") !== -1, i = r ? "&" : "?";
      a += i + o + "=" + e;
    }
    return a;
  };
})(Drupal);
//# sourceMappingURL=ajax.js.map
