(function(f, d) {
  let l = !1, c = null;
  f.behaviors.neoLoader = {
    show: (n, e, s, r) => {
      var o;
      if (e = e || "fullscreen", s = s || "body", r = r || 300, typeof d.neoLoader < "u" && typeof d.neoLoader.markup < "u") {
        const t = document.createElement("div");
        if (t.classList.add("ajax-progress"), t.classList.add("ajax-progress-" + e), t.innerHTML = d.neoLoader.markup, n) {
          const a = document.createElement("div");
          a.classList.add("message"), a.textContent = n, (o = t.querySelector(".neo-loader")) == null || o.appendChild(a);
        }
        if (s) {
          const a = typeof s == "string" ? document.querySelector(s) : s;
          if (a) {
            if (e === "fullscreen")
              a.appendChild(t);
            else {
              let i = a.closest(".js-form-item, form");
              i && i.classList.add("ajax-progress-wrapper"), a.after(t);
            }
            return c = setTimeout(() => {
              l = !1;
              const i = (u) => {
                u.target.classList.contains("ajax-progress") && (l = !0);
              };
              t.addEventListener("transitionend", i), t.classList.add("active");
            }, r), t;
          }
        }
      }
      return null;
    },
    hide: (n) => {
      c && clearTimeout(c);
      const e = document.querySelector(".ajax-progress");
      if (e) {
        if (e.classList.contains("active")) {
          const s = setInterval(() => {
            if (l) {
              clearInterval(s);
              const r = (o) => {
                o.target.classList.contains("ajax-progress") && (e.remove(), n && n());
              };
              e.addEventListener("transitionend", r), e.classList.remove("active");
            }
          }, 100);
        } else
          e.remove(), n && n();
        return e;
      }
      return null;
    }
  };
})(Drupal, drupalSettings);
//# sourceMappingURL=loader.js.map
