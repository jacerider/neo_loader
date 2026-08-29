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
declaration**, whose markup is the declaration's own. Nothing declares it and no declaration
may override it; a loader that needs a different class is a **loader plugin** instead.
_Avoid:_ "the YAML plugin class", "the fallback plugin".

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
