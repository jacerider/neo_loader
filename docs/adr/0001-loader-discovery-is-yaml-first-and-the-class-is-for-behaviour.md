# 0001 — Loader discovery is YAML-first, and a loader class is reserved for behaviour

**Status:** accepted · **Date:** 2026-08-28
**Context:** `neo_loader` — the `@Loader`/`#[Loader]` plugin type, the twelve loader classes it
shipped, and the **loader library** built from them
**Issue:** jacerider/neo_loader#1

**Decision.** A **loader** is declared as data by default: `{extension}.neo.loader.yml` is
discovered alongside the class-based plugin type, and every loader whose markup is a fixed string
is a **loader declaration** rendered by the **default loader class**. A **loader plugin** remains
supported and is reserved for a loader whose markup is computed. The stylesheet leaves the loader
entirely: the **loader manager** answers it from the definition, deriving `src/css/loader/{id}.css`
inside the declaring extension when nothing declares one, and consults an instance only as a
fallback for a class-based loader that declares no stylesheet of its own.

**Why it needs recording.** A plugin type with two discoveries and a stylesheet nothing declares
reads as a half-finished migration; both halves are load-bearing, and removing either later breaks
somebody's loader. The plugin type was more complicated than the thing it hid: eleven of the twelve
shipped classes held a fixed HTML string and a `setCssFile()` body rebuilding by hand a path every
one derived from its own id by the same rule — 452 lines of PHP, 34% of the module, for eleven
strings and a filename convention — and every cross-cutting edit was paid twelve times (each file
changed twice in eighteen months, both module-wide mechanical sweeps). Library building no longer
instantiates loaders to ask a path it can compute, which also stops `neo_icon`'s icon trait being
dragged into library discovery on every cache rebuild.

**Rejected.**
- Leave it as classes — works, and has for two years; but nothing in a loader without behaviour is
  a class's job, and the sweeps keep arriving.
- Move every loader to YAML and delete the plugin type — cleaner still, rejected on blast radius:
  the icon loader genuinely computes its markup, and the type is public in a package shipping to
  roughly thirty sites. A grep found no `@Loader` plugin outside this module on the planning site,
  which is one site's evidence about everyone's extension point.

**Cost.** `getLabel()` and the `$label` it read are removed outright rather than deprecated,
which the rest of this paragraph does not do and which is deliberate: the method read
`$this->configuration['label']`, nothing ever populated that key, and so every call it could
ever have received would have returned NULL. Nothing in the module called it and no caller was
found on the planning site. A deprecation cycle for a method that cannot have worked buys a
release of nothing; a third party who somehow called it was already getting NULL and gets a
fatal instead, which is the more honest of the two answers. The rest is kept: the annotation
spelling of the plugin type, `getCssFile()`, `setCssFile()` and the base class's `$path` are
deprecated rather than removed: the release adding YAML discovery changes
nothing a third-party loader relies on, and the removals wait for a later one, once sites are known
to be clear. A loader declared by an extension other than `neo_loader` needs its **derived
stylesheet** expressed root-relative, because the **loader library** it lands in is owned by
`neo_loader`; that adaptation lives at the one place libraries are emitted, so the derivation rule
stays a single sentence about the declaring extension.

**Release.** The `@deprecated` tags this decision adds name `neo_loader:1.1.0`, so the release
carrying it is cut **minor** — `pkg release neo_loader minor`. Cut as a patch, every notice
names a version that deprecated nothing.
