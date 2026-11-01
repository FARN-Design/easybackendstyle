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
