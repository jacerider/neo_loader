# 0003 — `neo_loader` ships no inline-build subscriber

**Status:** accepted · **Date:** 2026-08-28
**Context:** `neo_loader` — the **loader colour properties**, and where the **loader contrast
colour** is derived
**Issue:** jacerider/neo_loader#5

**Decision.** `neo_loader` registers no subscriber on `neo_build`'s inline-CSS event and declares
neither `--loader-bg` nor `--loader-text` at `:root`. The **loader colour properties** exist only
where a loader is rendered: the `neo_loader` theme hook writes both inline on each loader element,
deriving the **loader contrast colour** as the **loader colour** with `-content` appended. The
`NeoBuildInlineEventSubscriber` class that once did this is deleted, along with the commented-out
service definition that had disabled it since February 2025.

**Why it needs recording.** `neo_loader` is the one Neo package in this stack with no
`NeoBuildInlineEventSubscriber` — `neo_color`, `neo_font`, `neo_form`, `neo_tooltip` and
`neo_alchemist` all have one. Those emit tokens nothing else provides; this one would emit a
duplicate of a value the render path already writes. The rule had three homes and only one was
right: the subscriber spliced `content` in front of the shade, a token `neo_color` cannot emit
because `base-content` is registered as a colour family with no numbered shades; `d3b2c93` moved
the derivation into the theme hook and commented the service out rather than deleting the class;
`b158a1b` corrected the theme hook and left the subscriber untouched. A copy that cannot run and
disagrees with the copy that does is not a fallback but a second answer waiting to be believed.

**Rejected.**
- Repair and register the subscriber (what the initial commit intended) — it would put the
  properties at `:root` on every page of every site the package ships to, and in a different value
  convention from the theme hook's: it emitted `var(--color-…)`, a bare `R G B` triplet, where the
  theme hook writes `rgb(var(--color-…))`, a complete colour. The module's own stylesheet already
  holds one declaration of each kind; two sources for these names is the problem, not the solution.
- Delete only the service definition, keep the class — an unreachable copy of a rule is an
  invitation to re-enable it, and re-enabling it is the failure.

**Cost.** Anything needing the **loader contrast colour** outside a loader element derives it, as
the settings form's **loader preview** now does, or reads it from an enclosed loader element; the
two derivations agree, and consolidating them belongs to the plan that owns the theme hook. One
shipped rule is left permanently dead: `loader.css`'s `.ajax-progress-wrapper .ajax-progress` dims
its backdrop with `rgb(var(--loader-bg) / 0.5)`, which needs `--loader-bg` as a bare triplet on an
*ancestor* of the loader; nothing has met that since the subscriber was disabled and nothing now
can — a stylesheet defect, recorded for whoever fixes it, not a reason for a second derivation.
