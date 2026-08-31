# 0006 — The inline throbber carries the loader's own chip, and the dead dim is deleted rather than repaired

**Status:** accepted · **Date:** 2026-08-31
**Context:** `neo_loader` — the **loader chip** across the two **loader presentations**
**Issue:** jacerider/neo_loader#12

**Decision.** The **inline throbber**'s badge paints the **loader colour** behind the **loader
contrast colour**, as the **fullscreen overlay**'s does: the `background-color: transparent` that
made it chip-less is deleted, and so is the container's `background-color: rgb(var(--loader-bg) /
0.5)`. The dim is not repaired: deleting it closes the one shipped rule ADR 0003 recorded as
permanently dead and deliberately left unowned. The icon loader's `color: inherit` becomes
`color: var(--loader-text, inherit)`, so that one loader is not left taking the page's own text
colour onto a chip.

**Why it needs recording.** The obvious fix is the opposite one. The stylesheet was written as a
scrim with a transparent badge on it, and it worked while an inline-build subscriber declared
`--loader-bg` at `:root` as a bare `R G B` triplet. `d3b2c93` (2025-02-18) commented that
subscriber out and switched the badge's own usage to the complete-colour form the theme hook
writes, leaving the container on the old convention: `rgb(rgb(…) / 0.5)` is not a value, so the
backdrop has resolved to nothing on every request since that day, and ADR 0003 recorded the rule as
permanently dead without claiming its repair. A reader who finds a deliberate transparent badge
deleted will reach for that scrim, and it loses on arithmetic: half a dark **loader colour** over a
light card lands near mid grey, where the near-white contrast colour is roughly 3:1 — under 4.5:1
for the 10–12px text the badge carries. It fixes the defect halfway and dims the whole form item to
do it, when the pair is derived to hold on one surface and the chip is that surface. The icon rider
**reverses a decision the `neo-loader-contrast-color` plan recorded**: that plan weighed this exact
declaration and declined it as a shipped-CSS change against the explicit intent of `0bc24ea`
(2025-06-10), which made the icon type inherit and added the settings form's own preview wrapper to
feed it in one commit. The fallback form is what makes the reversal safe, since a loader rendered
with `color: FALSE` has no inline `--loader-text` and still inherits.

**Rejected.**
- Repair the dim, with or without the chip — ~3:1 on its own, a new dim wherever it is turned on,
  and a backdrop the chip has already made unnecessary.
- Recolour the throbber to the surface it lands on — the theme hook declares `--loader-text` inline
  on the element, so a stylesheet displaces it only with `!important` or JavaScript.
- Leave the icon loader alone — legible inline today only because the badge is transparent, so the
  chip without the rider breaks a working configuration.

**Cost.** Appearance changes on every installing site that turns "Always show loader as overlay"
off, with no setting to decline it, and the icon loader's colour changes on both presentations. Its
`padding: inherit` is left alone, so its chip still hugs the icon — colour has a safe fallback form
and padding has none. The browser check pins the invariant, not a ratio: the inline throbber's badge
computes a background equal to its own `--loader-bg` and not transparent, and a colour equal to its
own `--loader-text`, asserted for a shipped loader and for the icon loader on both presentations.
