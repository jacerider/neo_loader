# CONTEXT — neo_loader

Terms specific to `neo_loader`: the throbbers it renders, how they are declared and
discovered, and the stylesheets and libraries built from them. One entry per term: what it
IS, then the names not to use for it.

## Loaders and how they are declared

**Loader** — one throbber the module can render: an id, a human-readable label, a block of
markup and a stylesheet. It is the unit the settings form offers, the `neo_loader` render
element names, and the `neo_loader` theme hook renders. _Avoid:_ "throbber" as the name of
the definition (that is the thing on screen), "spinner", "loader plugin" for one that has no
class.

**Loader declaration** — a **loader** defined as data: one entry in an extension's
`{extension}.neo.loader.yml`, keyed by the loader id, carrying a label, its markup and
optionally a stylesheet. It has no class of its own; the **default loader class** renders it.
This is how every loader without behaviour is expressed. _Avoid:_ "YAML plugin", "the loaders
file", "definition" unqualified.

**Loader plugin** — a **loader** defined as a class under an extension's `Plugin/Loader`
namespace, carrying a `#[Loader]` attribute. It exists for the loaders whose markup is
computed rather than fixed — in this module, exactly one, the icon loader that asks
`neo_icon` for a spinner. _Avoid:_ "loader class" for the base or the default class, "the
annotation" (the annotation form is the retained legacy spelling of the same thing).

**Default loader class** — the plugin class the **loader manager** gives every **loader
declaration** that names none, whose markup is the declaration's own. It is the default of the
declaration's optional `class` key, not a fixed answer: a declaration may name another class
and the **derived stylesheet** rule still applies to it. Nothing shipped here names one — a
loader that needs real behaviour is written as a **loader plugin** instead — so the default is
what every one of the eleven declarations gets. _Avoid:_ "the YAML plugin class", "the
fallback plugin".

**Derived stylesheet** — the stylesheet a **loader** gets when it declares none: the path
`src/css/loader/{id}.css` inside the declaring extension, with the id's underscores written
as dashes. It is a rule the **loader manager** applies to a definition, never a value a
loader carries. _Avoid:_ "the CSS file", "the css path", and treating it as data.

**Loader library** — the asset library `neo_loader` generates per **loader**, named
`neo_loader/plugin.{id}` with the id's underscores written as dashes, attaching that loader's
stylesheet and nothing else. It is built from definitions alone: nothing is instantiated to
name one. _Avoid:_ "the plugin library", "the loader CSS library".

**Active loader** — the **loader** named by the `loader` setting, which is the one every page
attaches and renders when no caller names another. _Avoid:_ "the default loader" (that is the
render element's own `wave`), "the site loader".

## Attaching the loader

**Loader-enabled element** — a render element carrying a truthy `#loader` property, which the
module's element pre-render marks with the `use-neo-loader` class and the loader library so the
front end shows a loader while that element's request is in flight. Only four element types
receive that pre-render — link, button, submit and the modal link — so the property is inert on
anything else. _Avoid:_ "loader element" (that is the `neo_loader` render element, which renders a
**loader**, not an element that triggers one), "throbber element".

**Loader message** — the text a **loader-enabled element** shows beside its loader, carried on the
element as `data-neo-loader-message`. It is set only when the element's `#loader` value is a string
or a markup object; `TRUE` or a render array asks for a loader with no message. _Avoid:_ "the
loader title" (that is the `neo_loader` theme hook's own `title` variable), "loader label".

**Autosubmit element** — an input element carrying a truthy `#autosubmit` property, which the
module's element pre-render marks with the `use-neo-autosubmit` class and the autosubmit library so
a change to it submits its form without a button. Every element declaring itself an input receives
that pre-render, not a named list. _Avoid:_ "auto-submit" hyphenated, "the autosubmit behaviour"
for the element that carries it.

**Ajax override library** — `neo_loader/loader-ajax`, the library that replaces core's ajax
progress indicators with the **active loader**. It is attached by making core's own ajax library
depend on it, which is why it loads before the object it patches. _Avoid:_ "the ajax library" —
`neo_loader/ajax` is a different library doing an unrelated job, loading a URL when a **loader**
comes into view — and "the throbber override".

**Ajax dependency inversion** — the module making core's ajax library depend on the **ajax
override library**, rather than the override library depending on core's. It is what attaches the
override wherever core's ajax is attached, and at no cost on a page that has no ajax at all. See
ADR 0004. _Avoid:_ "the library alter", "the ajax hack", "the dependency flip".

**Ajax progress override** — the five replacements the **ajax override library** installs on
core's ajax prototype, which route core's progress indicators through the **active loader** and
fall back to core's own whenever no loader markup is available. They are installed once per
document, on the first behaviour pass after core's ajax script has run. _Avoid:_ "the monkey
patches", "the throbber override" (that names one of the five), "the ajax overrides" as a name for
the library that carries them.

## The two presentations

**Loader presentation** — one of the two shapes the **ajax progress override** gives a **loader**:
the **fullscreen overlay** or the **inline throbber**. Which one a request gets is decided by the
`always_fullscreen` setting, not by the request. _Avoid:_ "the loader type", "the progress
indicator", "loader mode".

**Fullscreen overlay** — the **loader presentation** that covers the viewport: a scrim over the
whole page with the **loader badge** docked at the top. _Avoid:_ "the overlay" unqualified, "the
modal loader", "the full-page loader".

**Inline throbber** — the **loader presentation** placed after its **throbber anchor**, covering
that anchor's closest form item rather than the page — or standing on its own where the anchor has
no form item to cover. It is what a site gets with "Always show loader as overlay" off, and it is
sized to join a line of content rather than to be read across a room: the line box it lands in
measures the same height with it as without. _Avoid:_ "the throbber" unqualified, "the small
loader", "the in-place loader".

**Throbber anchor** — the element the **ajax progress override** inserts an **inline throbber**
after, and the origin of the form item that throbber covers: the closest
`[data-drupal-ajax-container]` when the triggering element has one, and the triggering element
otherwise. It is core's own rule, followed rather than reinvented, so a throbber never lands
inside a dropbutton or a contextual-links placeholder. _Avoid:_ "the trigger" (that is the element
whose request it is), "the insertion point", "the progress container".

**Loader badge** — the rendered **loader** as it appears inside a **loader presentation**: its
shapes, the ajax message and, on a **held overlay**, the **dismissal hint**, together on the
**loader chip**. _Avoid:_ "the loader box", "the throbber" for it, "the badge" where the
presentation is meant.

**Loader chip** — the rounded background a **loader badge** paints from `--loader-bg`, and the
surface its **loader contrast colour** is readable on. The two are a derived pair and hold only
where one element carries both. See ADR 0006. _Avoid:_ "the pill", "the loader background", "the
scrim" (that is the **fullscreen overlay**'s backdrop).

## The settings form

**Loader gallery** — every declared **loader** rendered at once on the loader settings form,
each on its own **loader chip** in the configured **loader colour** and each selectable in place,
which is how the `loader` setting is chosen. It shows the loaders' markup and motion and nothing
of the ajax progress path. _Avoid:_ "the loader preview" (the single active-loader render beside
a select that it replaces), "the throbber select", "the picker", "the sample", "the demo".

**Gallery tile** — one **loader** in the **loader gallery**: the rendered loader on its chip,
the loader's own label, and the radio that selects it, as one click target. _Avoid:_ "the gallery
item", "the swatch", "the card", "the option".

**Loader test** — the pair of controls on the settings form, one per **loader presentation**,
each firing a no-op ajax round trip so the **active loader** appears through the same
progress-indicator path every ajax request on the site uses, as a **held overlay**. Both reach
their presentation whatever the `always_fullscreen` setting says: the overlay control through
core's own ajax progress type, the inline one through a **presentation pin**. Neither saves
anything. _Avoid:_ "the preview", "the test button" (there are two), "the test loader" used for
the **loader gallery**.

**Presentation pin** — the `data-neo-loader-presentation` attribute a control carries to choose
the **loader presentation** for its own request regardless of the `always_fullscreen` setting,
read off the triggering element by the **ajax progress override** exactly where the hold is read.
Its values are `fullscreen` and `throbber`, the names the loader behaviour already takes. Like
the hold it is internal to the **loader test**, not a surface a site may use. _Avoid:_ "the mode
attribute", "the presentation override", "the fullscreen flag".

**Held overlay** — a **loader presentation** that stays on screen after its request's response has
landed, instead of being torn down with it, and leaves only by dismissal. Either presentation can
be held. Only the **loader test**'s request asks for one, through a `data-neo-loader-hold`
attribute the control carries and the **ajax progress override** reads off the element that
triggered the request; it is a mechanism internal to that control, not a surface a site may use.
_Avoid:_ "the sticky loader", "the persistent overlay", "the paused loader".

**Dismissal hint** — the line a **held overlay** always shows saying how to dismiss it. It is not
the ajax message and does not follow the hide-ajax-message setting, because the case that needs
it most is the one where no message is shown. _Avoid:_ "the close message", "the overlay message"
(that is the ajax message).

## Settings in effect

**Route applicability** — the loader settings' answer to whether the current route gets the
**active loader** attached to the page: true on every non-admin route, and on an admin route only
when the admin-paths setting is on. It is the one question the page-attachment path asks of the
settings beyond reading their values. _Avoid:_ "the admin check", "route access", "is applicable".

**Loader colour** — the value of the `color` setting: a pallet id and a shade joined by a dash,
as the `neo_color` element stores it (`base-900` is the shipped default). It names the loader's
own background; the colour its shapes are painted in is the **loader contrast colour**, derived
from it. _Avoid:_ "the color setting" for the value, "the pallet", "the loader color name".

**Loader contrast colour** — the readable colour paired with a **loader colour**, held in
`neo_color`'s `--color-{pallet}-{shade}-content` token: the shade's own token name with
`-content` appended. Every shipped loader paints its shapes with it, the icon loader included —
that one reads it as `color`, and inherits from whatever encloses the loader only where no
**loader colour** was configured. _Avoid:_ "the content colour", "the text colour", and
`--color-{pallet}-content-{shade}`, which names no token `neo_color` emits.

**Loader colour properties** — `--loader-bg` and `--loader-text`, the two custom properties the
`neo_loader` theme hook writes inline on each loader element from the **loader colour** and its
**loader contrast colour**. They exist only on the elements that need them: nothing in the
module declares either at `:root`, so neither is readable from an ancestor. See ADR 0006.
_Avoid:_ "the loader variables", "the CSS vars", "the inline style" as the name of the pair.
