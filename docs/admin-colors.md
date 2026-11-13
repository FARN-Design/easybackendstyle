# Admin color stylesheet

The personalizedcolorscheme registration and saved ebs color keys are unchanged.
WordPress 7+ loads a packaged stylesheet; activation, saving, and updates never
rewrite plugin files. The pre-7.0 implementation is unchanged.

`build/admin-colors/modern.css` is a reviewed WordPress 7.1.4 source snapshot
(GPL-2.0-or-later, WordPress contributors). Its original path, SHA-256 and
explicit palette mapping are in `source.json`. Neutral colors are intentionally
allowlisted. Text colors for primary buttons and menu highlights are assigned
by context before palette substitution. RGB channels are separate from hex values.

Run `npm run build:colors`, then `npm run test:colors`. No npm installation is
needed for these Python-standard-library commands. CI compares generated output,
runs regressions, and compares the latest stable WordPress CSS with the reviewed
snapshot. A difference blocks release packaging and downstream deployment.
It does not automatically change the baseline or any compatibility claim.

For an upstream change, inspect the old/new CSS selectors and declarations,
update the snapshot and palette mapping, update version and SHA-256, then build.
A checksum update alone is not a compatibility review. Test existing saved colors,
default settings, menu/toolbar and hover/focus states, buttons, editor, RTL and
frontend toolbar in an actual WordPress installation before release. Check PHP
minimum and Playground separately as required by the repository release policy.

Optional local source comparison:
`python3 scripts/build-admin-colors.py --check --wordpress-root /path/to/wordpress`

The source snapshot is a build input, not a runtime dependency. Build files and
documentation are excluded from release packages. Existing CSS-generation option
flags can remain in the database; the runtime no longer reads them.


## First installation

Activation never selects a different admin scheme, including reactivation. New
installations start with the WordPress Modern field defaults and keep the current
user scheme until an explicit save. Existing confirmed plugin colors are preserved.

The optional **Import colors from theme** button appears below the page heading only
when the active theme provides at least one usable color. It requests the latest
Global Styles palette on each click, fills the form (including derived colors), and
never saves or selects a scheme. The normal Save button applies the result. Theme
updates or palette edits do not silently overwrite the user's backend settings.

Theme palette order is preserved. Global Styles and custom presets override matching
theme slugs. Core default colors are never imported. Presets are resolved through local
CSS variables; named CSS colors provide a fallback only when no usable presets exist.
Website background colors are not interpreted as backend menu colors.

Selection first excludes near-black, neutral and very pale colors when usable chromatic
colors exist. It prefers darker candidates, preserving their palette order. Primary is
capped at the existing luminance limit. Secondary is a darker HSL variation of primary,
with HSL lightness capped at 20%, saturation at 55%, and relative luminance at 0.08.
An existing dark, muted theme color within 25 hue degrees takes precedence. Tertiary prefers the closest hue within 65 degrees
(including tonal variations), with palette order breaking ties; otherwise it reuses
primary. This is a restrained design heuristic, not a perceptual color-distance metric.
A single usable theme color is sufficient. Pastel-only palettes retain their first
chromatic hue and darken it instead of using a preceding white surface. Neutral-only
palettes remain supported.

The Perspektive Übermorgen regression expects violet `#33258a`, dark violet `#1e164d`
and lavender `#837ac9`. Without the lavender swatch, cyan `#009bdc` is preferred to red
or yellow. The import still only fills the form; saving remains explicit.


The real WordPress regression test `scripts/test-theme-colors.mjs <plugin.zip>` installs
the selected standard theme, saves its palette through the Site Editor's Global Styles REST
endpoint before plugin activation, then checks inert activation, form-only import, palette
changes while the settings page is open, explicit saving and computed browser colors.
It defaults to Twenty Twenty-Five; `--theme=twentytwentythree` covers named roles. Run it with `--php=8.2` and `--php=8.5`; Chromium must be
installed via Playwright (or supplied with `--chromium=/path/to/browser`). Reports and
screenshots are written to `test-results/`.


When a theme supplies no usable palette, the settings start with WordPress Modern
colors (blue `#3858e9`, dark menu `#1e1e1e`, submenu `#0c0c0c`), verified against
WordPress 7.1.4. The existing user scheme stays active until saving. Settings saves
run on the page load hook before WordPress selects its CSS and body classes, so the
first save applies immediately without changing the user profile manually. Previously
saved plugin palettes are preserved; unconfirmed historical blue defaults are refreshed.


The default-palette browser regression selects WordPress's actual `modern` scheme
before activation. It compares computed colors before and after an unchanged save
(background, menu, toolbar, submenu, current menu item, submenu text, buttons and
links), then checks that editing one advanced color preserves every other field.
Modern submenu hover text has its own field because its light blue differs from
normal link-hover blue. Unconfirmed Fresh defaults are upgraded; confirmed palettes
are retained.

The settings page loads a page-local preview stylesheet and variables even before
the first save or while another user scheme is selected. Importing or manually
changing a main color immediately updates this preview. Neither the stored palette
nor the user scheme changes until Save. Other admin pages keep the selected scheme.
The browser regression checks first-import and manual-primary previews before saving.
