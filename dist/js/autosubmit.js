(function(e, u) {
  function l(t, o, c = 300) {
    if (!t) {
      console.error("Element not found");
      return;
    }
    if (!d(o)) {
      console.error("Submit element is not a submit button:", t);
      return;
    }
    o.classList.add("sr-only");
    const i = o.closest(".form-actions");
    i instanceof HTMLElement && (f(i).length || i.classList.add("sr-only"));
    let s, n = t.type === "checkbox" || t.type === "radio" ? t.checked : t.value;
    const a = () => {
      const r = t.type === "checkbox" || t.type === "radio" ? t.checked : t.value;
      r !== n && (n = r, s && clearTimeout(s), s = window.setTimeout(() => {
        e.behaviors.neoLoader.show("Loading..."), o.click();
        const h = new MouseEvent("mousedown", {
          bubbles: !0,
          cancelable: !0,
          button: 0,
          // Simulate left-click
          clientX: 200,
          clientY: 150
        });
        o.dispatchEvent(h);
      }, c));
    };
    t.type === "checkbox" || t.type === "radio" ? t.addEventListener("change", a) : (t.tagName === "TEXTAREA" || t.tagName === "INPUT" && (t.type === "text" || t.type === "email" || t.type === "password" || t.type === "search" || t.type === "url" || t.type === "tel") || t.addEventListener("change", a), t.addEventListener("blur", a));
  }
  function d(t) {
    return t.tagName === "BUTTON" && t.type === "submit" || t.tagName === "INPUT" && t.type === "submit";
  }
  function f(t) {
    const o = [];
    if (t) {
      const c = t.querySelectorAll("a, button, input");
      for (let i = 0; i < c.length; i++) {
        const s = c[i], n = window.getComputedStyle(s);
        n.display !== "none" && n.visibility !== "hidden" && s.classList.contains("sr-only") === !1 && s.classList.contains("hidden") === !1 && parseFloat(n.opacity) > 0 && o.push(s);
      }
    }
    return o;
  }
  e.behaviors.neoAutosubmit = {
    attach: function(t) {
      u("neo-loader", ".use-neo-autosubmit", t).forEach((o) => {
        if (!(o instanceof HTMLInputElement || o instanceof HTMLSelectElement || o instanceof HTMLTextAreaElement)) {
          console.warn("Element is not an input, select, or textarea:", o);
          return;
        }
        const c = o.closest("form");
        if (c) {
          const i = c.querySelectorAll(".form-actions");
          if (i.length > 0) {
            const n = i[i.length - 1].querySelector(".form-submit");
            n && l(o, n);
          }
        }
      });
    }
  };
})(Drupal, once);
//# sourceMappingURL=autosubmit.js.map
