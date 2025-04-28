(function(f, u, d) {
  let c = !1, l = null;
  f.behaviors.neoLoader = {
    attach: function(a) {
      u("neo-loader", ".use-neo-loader", a).forEach((e) => {
        e.addEventListener("click", (s) => {
          const n = e.getAttribute("data-neo-loader-message") || f.t("Loading..."), r = parseInt(e.getAttribute("data-neo-loader-type") || "fullscreen"), t = parseInt(e.getAttribute("data-neo-loader-delay") || "0");
          this.show(n, r, e, t);
        });
      });
    },
    show: (a, e, s, n) => {
      var r;
      if (e = e || "fullscreen", s = s || "body", n = typeof n > "u" ? 1e3 : n + 10, typeof d.neoLoader < "u" && typeof d.neoLoader.markup < "u") {
        const t = document.createElement("div");
        if (t.classList.add("ajax-progress"), t.classList.add("ajax-progress-" + e), t.innerHTML = d.neoLoader.markup, a) {
          const o = document.createElement("div");
          o.classList.add("message"), o.textContent = a, (r = t.querySelector(".neo-loader")) == null || r.appendChild(o);
        }
        if (s) {
          const o = typeof s == "string" ? document.querySelector(s) : s;
          if (o) {
            if (e === "fullscreen")
              o.appendChild(t);
            else {
              let i = o.closest(".js-form-item, form");
              i && i.classList.add("ajax-progress-wrapper"), o.after(t);
            }
            return l = setTimeout(() => {
              c = !1;
              const i = (L) => {
                L.target.classList.contains("ajax-progress") && (c = !0);
              };
              t.addEventListener("transitionend", i), t.classList.add("active");
            }, n), t;
          }
        }
      }
      return null;
    },
    hide: (a) => {
      l && clearTimeout(l);
      const e = document.querySelector(".ajax-progress");
      if (e) {
        if (e.classList.contains("active")) {
          const s = setInterval(() => {
            if (c) {
              clearInterval(s);
              const n = (r) => {
                r.target.classList.contains("ajax-progress") && (e.remove(), a && a());
              };
              e.addEventListener("transitionend", n), e.classList.remove("active");
            }
          }, 100);
        } else
          e.remove(), a && a();
        return e;
      }
      return null;
    }
  };
})(Drupal, once, drupalSettings);
//# sourceMappingURL=loader.js.map
