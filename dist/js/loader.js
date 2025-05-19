(function(c, u, i) {
  let d = null;
  c.behaviors.neoLoader = {
    attach: function(s) {
      u("neo-loader", ".use-neo-loader", s).forEach((e) => {
        e.addEventListener("click", (t) => {
          const a = e.getAttribute("data-neo-loader-message") || c.t("Loading..."), r = parseInt(e.getAttribute("data-neo-loader-type") || "fullscreen"), n = parseInt(e.getAttribute("data-neo-loader-delay") || "0");
          this.show(a, r, "body", n);
        });
      });
    },
    show: (s, e, t, a) => {
      var r;
      if (e = e || "fullscreen", t = t || "body", a = Math.max(typeof a > "u" ? 0 : a, 10), typeof i.neoLoader < "u" && typeof i.neoLoader.markup < "u") {
        const n = document.createElement("div");
        if (n.classList.add("ajax-progress"), n.classList.add("ajax-progress-" + e), n.innerHTML = i.neoLoader.markup, s) {
          const o = document.createElement("div");
          o.classList.add("message"), o.textContent = s, (r = n.querySelector(".neo-loader")) == null || r.appendChild(o);
        }
        if (t) {
          const o = typeof t == "string" ? document.querySelector(t) : t;
          if (o) {
            if (e === "fullscreen")
              o.appendChild(n);
            else {
              let l = o.closest(".js-form-item, form");
              l && l.classList.add("ajax-progress-wrapper"), o.after(n);
            }
            return d = setTimeout(() => {
              n.classList.add("active");
            }, a), n;
          }
        }
      }
      return null;
    },
    hide: (s) => {
      d && clearTimeout(d);
      const e = document.querySelector(".ajax-progress:not(.ajax-hiding)");
      if (e) {
        if (e.classList.add("ajax-hiding"), e.classList.contains("active")) {
          const t = (a) => {
            a.target instanceof HTMLElement && (a.target.removeEventListener("transitionend", t), a.target.remove());
          };
          e.addEventListener("transitionend", t), setTimeout(() => {
            e.classList.remove("active");
          });
        } else
          e.remove();
        return s && s(), e;
      }
      return null;
    }
  };
})(Drupal, once, drupalSettings);
//# sourceMappingURL=loader.js.map
