# 0001 — Loader discovery is YAML-first, and a loader class is reserved for behaviour

**Status:** accepted
**Date:** 2026-08-28
**Context:** `neo_loader` — the `@Loader`/`#[Loader]` plugin type, the twelve loader classes it
shipped, and the **loader library** built from them
**Plan:** `docs/plans/neo-loader-declarations/` on `wps`

## Decision

A **loader** is declared as data by default. `{extension}.neo.loader.yml` is discovered
alongside the class-based plugin type, and every loader whose markup is a fixed string is a
**loader declaration** rendered by the **default loader class**. A **loader plugin** — a class
carrying the attribute — remains supported and is the form reserved for a loader whose markup
is computed. The stylesheet leaves the loader entirely: the **loader manager** answers it from
the definition, deriving `src/css/loader/{id}.css` inside the declaring extension when nothing
declares one, and instances are consulted for it only as a fallback for a class-based loader
that declares no stylesheet of its own.

## Why

The plugin type was more complicated than the thing it hid. Eleven of the twelve shipped
classes contained a fixed HTML string and a `setCssFile()` body that rebuilt, by hand, a path
every one of the twelve derived from its own id by the same rule — 452 lines of PHP, 34% of the
module, to express eleven strings and a filename convention. The cost was not the lines: it was
that every cross-cutting edit to the module was paid twelve times, which the churn shows
plainly — those files changed twice each in eighteen months and both were module-wide mechanical
sweeps.

Two alternatives were real. **Leave it as classes** is what the module has done for two years,
and it works; it is rejected because nothing in a loader without behaviour is a class's job, and
the sweeps keep arriving. **Move every loader to YAML and delete the plugin type** is cleaner
still and was rejected on blast radius: the icon loader genuinely computes its markup, and the
type is public in a package that ships to roughly thirty sites. A grep found no `@Loader` plugin
outside this module in the site that planned this, but that is one site's evidence about
everyone's extension point.

Keeping both discoveries is therefore the decision, not an accident of migration. It is
hard to reverse — removing either one later breaks somebody's loader — and it is surprising
enough to be worth writing down, because a plugin type with two discoveries and a stylesheet
that nothing declares reads as a half-finished migration until you know that both halves are
load-bearing.

## Consequences

The annotation spelling of the plugin type, `getCssFile()`, `setCssFile()` and the base class's
`$path` are kept and deprecated rather than removed: the release that adds YAML discovery
changes nothing a third-party loader relies on, and the removals wait for a later one, once
sites are known to be clear. Library building stops instantiating loaders to ask a path it can
compute, which is also what stops `neo_icon`'s icon trait being dragged into library discovery
on every cache rebuild.

A loader declared by an extension other than `neo_loader` needs its **derived stylesheet**
expressed root-relative, because the **loader library** it lands in is owned by `neo_loader`.
That adaptation lives at the one place libraries are emitted, so the derivation rule itself
stays a single sentence about the declaring extension.
