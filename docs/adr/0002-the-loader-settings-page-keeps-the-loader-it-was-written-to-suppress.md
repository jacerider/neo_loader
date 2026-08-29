# 0002 — The loader settings page keeps the loader it was written to suppress

**Status:** accepted · **Date:** 2026-08-28
**Context:** `neo_loader` — **route applicability**, and the dead route comparison inside it
**Issue:** jacerider/neo_loader#3

**Decision.** **Route applicability** is true on every non-admin route, and on an admin route
exactly when the admin-paths setting is on. No route is exempt, the loader settings page included.
The comparison against `neo.loader` that was meant to exempt it is deleted rather than repaired to
name the route that exists, and the settings plugin stops reading the request entirely — that
comparison was its only use of the route name, so the request stack leaves the constructor with it.

**Why it needs recording.** Repairing the guard — swapping the dead name for the real one — is the
obvious reading, and it is the failure. The comparison has never fired: there is no `neo.loader`
route in this module or any other module or theme; the name is an eXo-era leftover, of a piece with
the `@file` docblock that still calls this the "eXo loader module". The settings page is served by
`neo.settings.plugin.neo_loader.config`, so it has always received the **active loader** like any
other admin page — and in the years the guard spent inert, the page grew a feature that depends on
it being inert. The **loader test** shows the **active loader** through the progress-indicator path
every ajax request uses, `neo_loader/loader` with its `drupalSettings.neoLoader` payload, attached
by the page-attachment hook only when **route applicability** says yes. Exempt the settings page
and the control silently shows nothing: what the original guard treated as noise on a configuration
screen is the one place in the interface where a site builder can see the overlay at all.

**Rejected.**
- Repair the comparison to name `neo.settings.plugin.neo_loader.config` — honours a two-year-old
  intention and breaks a working control; the control wins.
- Leave the inert line in place — a comparison naming a route that does not exist is an invitation
  to fix it, and fixing it is the failure.

**Cost.** A future need to exempt some route from the loader has no precedent to copy here, which
is correct: the exemption never worked, so nothing about its shape was ever proven. Anyone adding
one must also decide what happens to the **loader test** — the question this decision answers by
exempting nothing. **Route applicability** becomes a question about the admin context and one
setting, and nothing more.
