(function(c, u, i) {
  let d = null;
  c.behaviors.neoLoader = {
    attach: function(o) {
      u("neo-loader", ".use-neo-loader", o).forEach((e) => {
        e.addEventListener("click", (s) => {
          const n = e.getAttribute("data-neo-loader-message") || c.t("Loading..."), r = parseInt(e.getAttribute("data-neo-loader-type") || "fullscreen"), t = parseInt(e.getAttribute("data-neo-loader-delay") || "0");
          this.show(n, r, "body", t);
        });
      });
    },
    show: (o, e, s, n) => {
      var r;
      if (e = e || "fullscreen", s = s || "body", n = Math.max(typeof n > "u" ? 0 : n, 10), typeof i.neoLoader < "u" && typeof i.neoLoader.markup < "u") {
        const t = document.createElement("div");
        if (t.classList.add("ajax-progress"), t.classList.add("ajax-progress-" + e), t.innerHTML = i.neoLoader.markup, o) {
          const a = document.createElement("div");
          a.classList.add("message"), a.textContent = o, (r = t.querySelector(".neo-loader")) == null || r.appendChild(a);
        }
        if (s) {
          const a = typeof s == "string" ? document.querySelector(s) : s;
          if (a) {
            if (e === "fullscreen")
              a.appendChild(t);
            else {
              let l = a.closest(".js-form-item, form");
              l && l.classList.add("ajax-progress-wrapper"), a.after(t);
            }
            return d = setTimeout(() => {
              t.classList.add("active");
            }, n), t;
          }
        }
      }
      return null;
    },
    hide: (o) => {
      d && clearTimeout(d);
      const e = document.querySelector(".ajax-progress:not(.ajax-hiding)");
      return e ? (e.classList.add("ajax-hiding"), e.classList.contains("active") ? (e.classList.remove("active"), setTimeout(() => {
        e.remove();
      }, 150)) : e.remove(), o && o(), e) : null;
    }
  };
})(Drupal, once, drupalSettings);
//# sourceMappingURL=loader.js.map
