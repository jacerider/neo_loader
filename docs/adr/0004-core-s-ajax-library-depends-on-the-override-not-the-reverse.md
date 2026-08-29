# 0004 — Core's ajax library depends on the override, not the reverse

**Status:** accepted · **Date:** 2026-08-28
**Context:** `neo_loader` — the **ajax dependency inversion** and the **ajax progress override**
**Issue:** jacerider/neo_loader#6

**Decision.** The **ajax dependency inversion** stays: `neo_loader_library_info_alter()` appends
the **ajax override library** to core's ajax library's dependencies, the override declares none
back, and the **ajax progress override** is evaluated before the object it patches exists. Only
*when* it is installed changes: from a Drupal behaviour on the first pass after core's ajax script
has run, not from a timer that re-checked every ten milliseconds.

**Why it needs recording.** Drupal has no "attach me wherever that one is attached" declaration;
inverting the dependency is the only way to ride along, and it puts the override everywhere core's
ajax is — ajax, dialog and modal responses included, where `hook_page_attachments()` never runs —
free on a page with no ajax. The timer, mistaken for the inversion's cost, was strictly worse: in
production the two scripts are adjacent at the foot of the document, so its first check always
failed and the overrides landed ten milliseconds later, after `Drupal.attachBehaviors` had run its
first pass, so that pass's ajax requests went unpatched. A behaviour lands the override *inside*
that pass, before core's ajax behaviour binds anything, as `webform` does on this site.

**Rejected.**
- Normal dependency, attached from `hook_page_attachments()` — core's ajax library, jQuery and six
  further libraries on every HTML page of every site, ajax or not; and that conditional hook (no
  loader, or **route applicability** says no) would drop the override from routes that do use ajax.
- Declare `core/drupal.ajax` on the override library too — a cycle core does not survive:
  `LibraryDependencyResolver::doGetDependencies()` marks a library resolved only *after* recursing
  into its dependencies, so a two-library cycle never ends; `neo_loader.libraries.yml` has said so
  since July 2026, checked here against core 11.4.4.
- Inject the compiled file into core's ajax library's own `js` list (done until May 2025) — free
  ordering, but `neo_build` resolves compiled paths per build scope through a manifest, so no
  literal path exists to name; redoing that in a library alter is larger and more fragile still.

**Cost.** Attach runs on every ajax insert, so the override must be safe to reapply: each of the
five patches stashes the method it replaced under a fixed `…Original` property resolved at call
time, so a second pass makes each patched method its own predecessor and the next request recurses
until the stack ends; one sentinel, the first stash property being undefined, guards all five
atomically. The `libraries.yml` comment stays where `core/drupal.ajax` would be re-added, its
chunk-polling clause pointing here. Reopen this with a way to ride along without altering the other
library, or proof core's ajax is on every page anyway, leaving only the conditional-attachment case.
