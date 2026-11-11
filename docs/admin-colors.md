# Admin color stylesheet

The personalizedcolorscheme registration and saved ebs color keys are unchanged.
WordPress 7+ loads a packaged stylesheet; activation, saving, and updates never
rewrite plugin files. The pre-7.0 implementation is unchanged.

`build/admin-colors/blue.css` is a reviewed WordPress 7.1.4 source snapshot
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

New installations keep the user's existing admin color scheme until colors are saved,
unless the active theme supplies distinct `primary` and `secondary` colors. Existing
plugin installations retain their stored colors. Detection runs only during initial
setup; later theme changes and plugin reactivation do not overwrite saved choices.

Theme palette slugs in `theme.json` (including parent themes and classic editor palette
support) take precedence over CSS. Global Styles saved in the Site Editor override
theme defaults; custom user palette entries override theme entries with the same slug.
The built-in WordPress core palette is not treated as a theme color proposal. The fallback reads parent and child `style.css`
custom properties named `--wp--preset--color--primary`, `--color-primary`,
`--primary-color`, or `--primary`, with the same patterns for secondary and tertiary.
Hex colors and local CSS variable references are supported. Unnamed palette entries,
remote stylesheets, and ambiguous colors are not guessed.

Primary is darkened while retaining its hue until its relative sRGB luminance is no
higher than `#00bc6f`. Equal primary and secondary colors are rejected both before and
after adjustment. Missing tertiary uses secondary plus 10 HSL lightness percentage
points, clamped at white. Remaining colors are generated using the settings page's
lightness rules. Invalid or incomplete proposals leave the original scheme active.


The real WordPress regression test `scripts/test-theme-colors.mjs <plugin.zip>` installs
Twenty Twenty-Three, saves its palette through the Site Editor's Global Styles REST
endpoint before plugin activation, then checks imported values, persistence, reactivation,
and computed browser colors. Run it with `--php=8.2` and `--php=8.5`; Chromium must be
installed via Playwright (or supplied with `--chromium=/path/to/browser`). Reports and
screenshots are written to `test-results/`.


When a theme supplies no usable palette, the settings start with WordPress Fresh
colors (blue `#2271b1`, dark menu `#1d2327`, submenu `#2c3338`), verified against
WordPress 7.1.4. The existing user scheme stays active until saving. Settings saves
run on the page load hook before WordPress selects its CSS and body classes, so the
first save applies immediately without changing the user profile manually. Previously
saved plugin palettes are preserved; unconfirmed historical blue defaults are refreshed.
