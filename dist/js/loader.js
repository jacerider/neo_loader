(function(f, u, d) {
  let c = !1, l = null;
  f.behaviors.neoLoader = {
    attach: function(o) {
      u("neo-loader", ".use-neo-loader", o).forEach((e) => {
        e.addEventListener("click", (a) => {
          const s = e.getAttribute("data-neo-loader-message") || f.t("Loading..."), r = parseInt(e.getAttribute("data-neo-loader-type") || "fullscreen"), t = parseInt(e.getAttribute("data-neo-loader-delay") || "0");
          this.show(s, r, e, t);
        });
      });
    },
    show: (o, e, a, s) => {
      var r;
      if (e = e || "fullscreen", a = a || "body", s = Math.max(typeof s > "u" ? 200 : s, 10), typeof d.neoLoader < "u" && typeof d.neoLoader.markup < "u") {
        const t = document.createElement("div");
        if (t.classList.add("ajax-progress"), t.classList.add("ajax-progress-" + e), t.innerHTML = d.neoLoader.markup, o) {
          const n = document.createElement("div");
          n.classList.add("message"), n.textContent = o, (r = t.querySelector(".neo-loader")) == null || r.appendChild(n);
        }
        if (a) {
          const n = typeof a == "string" ? document.querySelector(a) : a;
          if (n) {
            if (e === "fullscreen")
              n.appendChild(t);
            else {
              let i = n.closest(".js-form-item, form");
              i && i.classList.add("ajax-progress-wrapper"), n.after(t);
            }
            return l = setTimeout(() => {
              c = !1;
              const i = (L) => {
                L.target.classList.contains("ajax-progress") && (c = !0);
              };
              t.addEventListener("transitionend", i), t.classList.add("active");
            }, s), t;
          }
        }
      }
      return null;
    },
    hide: (o) => {
      l && clearTimeout(l);
      const e = document.querySelector(".ajax-progress");
      if (e) {
        if (e.classList.contains("active")) {
          const a = setInterval(() => {
            if (c) {
              clearInterval(a);
              const s = (r) => {
                r.target.classList.contains("ajax-progress") && e.remove();
              };
              e.addEventListener("transitionend", s), e.classList.remove("active");
            }
          }, 10);
        } else
          e.remove();
        return o && o(), e;
      }
      return null;
    }
  };
})(Drupal, once, drupalSettings);
//# sourceMappingURL=loader.js.map
