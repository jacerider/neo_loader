(function(c, u, i) {
  let d = null;
  c.behaviors.neoLoader = {
    attach: function(n) {
      u("neo-loader", ".use-neo-loader", n).forEach((e) => {
        e.addEventListener("click", (a) => {
          const s = e.getAttribute("data-neo-loader-message") || c.t("Loading..."), r = parseInt(e.getAttribute("data-neo-loader-type") || "fullscreen"), t = parseInt(e.getAttribute("data-neo-loader-delay") || "0");
          this.show(s, r, e, t);
        });
      });
    },
    show: (n, e, a, s) => {
      var r;
      if (e = e || "fullscreen", a = a || "body", s = Math.max(typeof s > "u" ? 0 : s, 10), typeof i.neoLoader < "u" && typeof i.neoLoader.markup < "u") {
        const t = document.createElement("div");
        if (t.classList.add("ajax-progress"), t.classList.add("ajax-progress-" + e), t.innerHTML = i.neoLoader.markup, n) {
          const o = document.createElement("div");
          o.classList.add("message"), o.textContent = n, (r = t.querySelector(".neo-loader")) == null || r.appendChild(o);
        }
        if (a) {
          const o = typeof a == "string" ? document.querySelector(a) : a;
          if (o) {
            if (e === "fullscreen")
              o.appendChild(t);
            else {
              let l = o.closest(".js-form-item, form");
              l && l.classList.add("ajax-progress-wrapper"), o.after(t);
            }
            return d = setTimeout(() => {
              t.classList.add("active");
            }, s), t;
          }
        }
      }
      return null;
    },
    hide: (n) => {
      d && clearTimeout(d);
      const e = document.querySelector(".ajax-progress");
      if (e) {
        if (e.classList.contains("active")) {
          const a = (s) => {
            s.target.classList.contains("ajax-progress") && e.remove();
          };
          e.addEventListener("transitionend", a), e.classList.remove("active");
        } else
          e.remove();
        return n && n(), e;
      }
      return null;
    }
  };
})(Drupal, once, drupalSettings);
//# sourceMappingURL=loader.js.map
