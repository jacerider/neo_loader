(function(e) {
  e.behaviors.neoLoaderAjax = {
    attach: (s) => {
      once("neo-loader-ajax", "[data-loader-url]").forEach((a) => {
        var o = new IntersectionObserver((r, t) => {
          r.forEach((c) => {
            if (c.intersectionRatio > 0) {
              t.disconnect();
              const n = {
                url: a.dataset.loaderUrl,
                progress: !1,
                wrapper: a.id,
                effect: "fade",
                httpMethod: "GET"
              };
              e.ajax(n).execute();
            }
          });
        });
        o.observe(a);
      });
    }
  };
})(Drupal);
//# sourceMappingURL=ajax.js.map
