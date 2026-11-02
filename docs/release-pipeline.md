# Easy Backend Style release pipeline

The common PHP/JavaScript/browser test suite comes from `plugin-tests.yml` in
FARN-Design/ci-workflow. Publication comes from its shared `plugin-deploy.yml`;
this plugin selects `release_targets: '["wordpress-org"]'`. The same shared
publisher supports FARN or both destinations. Both shared workflows are generated snapshots pinned to
`75d814ac14a12a748efd80c29e5fe9e7cfd211d3`. GitHub does not allow a public
repository to call reusable workflows in a private repository, so callers use
`./.github/workflows/farn-plugin-tests.yml` and `farn-plugin-deploy.yml`.
The logic remains maintained centrally, and `.github/farn-workflows.json` records
its source commit and SHA-256 checksums. CI checks the snapshots before tests.
Do not edit generated copies; update from the central repository with:

```sh
python3 ../ci-workflow/scripts/sync-plugin-workflows.py --target . --ref <reviewed-commit>
python3 ../ci-workflow/scripts/sync-plugin-workflows.py --target . --ref <reviewed-commit> --check
```

Commit the two generated workflows and their manifest together. Their only
trigger is `workflow_call`; publication still requires a release event.
Plugin-specific color builds,
translation generation and historical migration fixtures stay here.

## Gates

Pushes to main/codex branches, pull requests and manual verification run:

1. Resolve the latest stable WordPress; validate minimum PHP and preview metadata.
2. Rebuild and validate all German catalogs and import files; compare the pinned
   color scheme against that WordPress release and run color regressions.
3. Common native PHPUnit, Vitest and Chromium tests on PHP 8.2, 8.3, 8.4 and 8.5.

Release events reuse this verification and then run:

`verify -> package -> compatibility -> shared publication`

The package is built with production dependencies, compiled catalogs and reviewed
color CSS. Stable changelog generation runs **before** packaging. The source
checkout/main branch is not rewritten by releases. The ZIP and PO files receive
SHA-256 checksums. Only allowlisted runtime directories enter the ZIP.

Both PHP 8.2 (declared minimum) and 8.5 (Blueprint) lint the actual packaged PHP
and load its Composer dependencies. The actual ZIP is installed in fresh SQLite
Playgrounds on the resolved WordPress release: clean install, an existing color
table, reactivation and a subsequent PHP request must preserve the saved color.
Both German documentation links and English rendering are checked. The WordPress 6.9 compatibility branch is also exercised on both PHP versions. Reports are
retained as Actions artifacts. The shared browser suite checks the settings form,
color picker, saving/reloading and resulting button color.

Prereleases publish the tested ZIP and `plugin-de_DE.po`/`plugin-de_DE_formal.po`
as GitHub release assets; they never generate a changelog or deploy to WordPress.org.
Stable publication/promotion triggers exactly one stable run, including all tests.
Editing a release does not redeploy. The marketplace job extracts the tested ZIP;
it never rebuilds from main or installs different dependencies.

## Release checklist

- Run the verification workflow successfully on the intended commit.
- Publish a prerelease first and inspect the package compatibility reports.
- Inspect the actual Playground settings page; activation alone is insufficient.
  The public preview in `.wordpress-org/blueprints/blueprint.json` uses PHP 8.5
  and latest stable WordPress and lands on the plugin settings page. The public blueprint installs the plugin and preserves its existing demo colors;
  the test blueprint activates the correctly cased local main file. Preview visibility remains a separate WordPress.org committer setting.
- After testing latest stable WordPress, deliberately update `Tested up to` in
  README.txt. Stable release CI refuses an out-of-date claim; it never raises it.
- Publish a stable release/tag. Existing `FARN_CORE_TOKEN`, `OPEN_AI_API_KEY` and
  SVN credentials are used; prereleases require neither OpenAI nor SVN credentials.

No GitHub run, tag, release or deployment is created by the local checks.

## Runtime safeguards

- Anonymous requests run no color-table SQL and emit no plugin CSS. Logged-in
  frontend requests only style a visible adminbar, with variables scoped to
  `#wpadminbar`. The WordPress 6 branch follows the same rule.
- Colors are loaded in bulk and cached for the request. Settings writes use one
  validated SQL statement and verify the result before reporting success.
- Schema changes run during activation or authorized admin requests. Failed
  creation returns an error without recursion. Migrations have a completion
  version and a short-lived lock, and can retry after an interrupted/failed write.
- The original legacy table and `ebs_legacy_colors_backup` are retained. Unknown
  historical fields remain in the backup; existing modern installations keep
  their current colors. No automatic cleanup deletes that recovery data.
- Native MySQL and packaged SQLite tests exercise failed writes/creation, retries,
  missing old values, migration mappings, existing settings and guest/adminbar
  behavior. `tests/support/hardening.php` is shared between the two database tests.

Production code has no external Composer dependency. `composer.lock` pins the
PHP test tools against PHP 8.2; `composer install --no-dev` builds the small PSR-4
autoloader for the release. No FARN Core or Strauss runtime is packaged.

## Local checks

```
python3 scripts/build-translations.py --check-source
python3 scripts/build-admin-colors.py --check
python3 tests/test_admin_colors.py
python3 tests/release/test_release.py
php tests/test_admin_color_runtime.php
php tests/test_settings_requests.php
npm ci
npm run test
composer install --no-dev --prefer-dist
python3 scripts/release.py --version 3.2.6-rc.1 --output /tmp/ebs-candidate
python3 scripts/release.py --package /tmp/ebs-candidate/easybackendstyle
FARN_TEST_PHP_VERSION=8.2 FARN_TEST_WP_VERSION=7.1.4 node scripts/test-package.mjs /tmp/ebs-candidate/easybackendstyle.zip
FARN_TEST_PHP_VERSION=8.5 FARN_TEST_WP_VERSION=7.1.4 node scripts/test-package.mjs /tmp/ebs-candidate/easybackendstyle.zip
```

Use a fresh output path each time. Resolve the current WordPress version instead
of carrying forward the example version. Native PHPUnit additionally requires the
matching official WordPress PHPUnit fixtures and a disposable MySQL database;
the shared workflow provisions both. Never point it at a developer/customer site.
