# 0005 — The loader test holds its own overlay, rather than every overlay gaining a minimum display time

**Status:** accepted · **Date:** 2026-08-29
**Context:** neo_loader — the **loader test**, the **held overlay**, and the **ajax progress override**'s teardown
**Issue:** jacerider/neo_loader#8

**Decision.** The **held overlay** is a property of one request: the **loader test**'s control
carries a `data-neo-loader-hold` attribute, the fullscreen progress override reads it off the
element that triggered the request, and only that overlay survives its response. Teardown resolves
the overlay belonging to the request that produced it instead of the first one a document-wide
lookup finds. No overlay anywhere else gains a floor on its time on screen.

**Why it needs recording.** The obvious fix is the opposite one — give `hide()` a minimum display
time so no overlay is ever torn down mid-reveal — and a reader who finds a request-scoped hold
instead will read it as the narrow version of a general fix. It is not. The **ajax override
library** is attached wherever core's ajax is, so a floor in teardown applies to every ajax
request on every page of every installing site, and the loader's reveal needs 0.3 s for the badge
and 1.3 s before the scrim even starts. A floor large enough to make the loader test perceptible
would make every autocomplete, every views filter and every inline form on every site feel that
much slower, in exchange for an affordance one person uses while configuring a throbber. The
second surprise is smaller and in the same direction: this reverses a decision recorded a day
earlier, which declined a browser-side hold as unnecessary. That plan's own verification measured
peak scrim opacity 0 and the badge never entering the viewport across every sampled frame of every
click. The reversal is the measurement.

**Rejected.**
- A minimum display time on every overlay — slows every ajax request everywhere to fix an admin
  control; likely to win later only if the reveal timings are shortened first.
- Deleting the loader test control — leaves nothing that shows the overlay presentation at all,
  and the **loader preview** exercises none of the progress path.
- A settings-page behaviour that builds and holds its own overlay — the control's whole value is
  that what it shows arrives by the real progress-indicator path, and a private copy would drift.
- A hold carried in the ajax options rather than on the element — invisible to anything inspecting
  the page, and matches none of the `data-neo-loader-*` vocabulary already read off elements.

**Cost.** Teardown now has two shapes — resolve-and-remove, and skip-because-held — so a future
change to it has a case to get wrong, and a held overlay that loses its dismissal listeners would
strand a full-page layer. The browser check pins both directions: Esc and a click each leave no
overlay node and no loading class, and an unrelated request neither tears a held overlay down nor
is itself held.
