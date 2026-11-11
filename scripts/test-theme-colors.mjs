import { runCLI } from '@wp-playground/cli';
import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, join } from 'node:path';
const root = resolve(import.meta.dirname, '..');
const argument = name => process.argv.find(value => value.startsWith(name + '='))?.slice(name.length + 1);
const php = argument('--php') || process.env.FARN_TEST_PHP_VERSION || '8.5';
const wp = argument('--wp') || process.env.FARN_TEST_WP_VERSION || 'latest';
const executablePath = argument('--chromium') || process.env.EBS_CHROMIUM_PATH;
const archive = resolve(process.argv[2] || 'output/easybackendstyle.zip');
const report = { php, wp, checks: [] };
mkdirSync(join(root, 'test-results'), { recursive: true });
const site = await runCLI({ command: 'server', php, wp, port: 9401, quiet: true,
  blueprint: { steps: [
    { step: 'defineWpConfigConsts', consts: { DISABLE_WP_CRON: true } },
    { step: 'installTheme', themeData: { resource: 'wordpress.org/themes', slug: 'twentytwentythree' }, options: { activate: true } },
    { step: 'installPlugin', pluginData: { resource: 'vfs', path: '/artifacts/easybackendstyle.zip' }, options: { activate: false } },
    { step: 'login', username: 'admin', password: 'password' },
  ] }, mount: [{ hostPath: archive, vfsPath: '/artifacts/easybackendstyle.zip' }],
});
async function run(name, code) {
  const result = await site.playground.run({ code: `<?php require '/wordpress/wp-load.php'; wp_set_current_user(1); ${code}` });
  report.checks.push({ name, exitCode: result.exitCode, text: result.text, errors: result.errors });
  console.log(name + ': ' + result.text);
  if (result.exitCode !== 0 || !result.text.includes('EBS_OK')) throw new Error(name + ': ' + result.text + result.errors);
}
try {
  await run('save-editor-colors-before-activation', `
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (is_plugin_active('easybackendstyle/easyBackendStyle.php')) throw new Exception('Plugin already active');
    $id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
    $request = new WP_REST_Request('POST', '/wp/v2/global-styles/' . $id);
    $request->set_param('settings', ['color'=>['palette'=>['theme'=>[
      ['slug'=>'primary','name'=>'Primary','color'=>'#246b45'],
      ['slug'=>'secondary','name'=>'Secondary','color'=>'#24364b'],
      ['slug'=>'tertiary','name'=>'Tertiary','color'=>'#557799'],
    ]]]]);
    $response = rest_do_request($request);
    if ($response->get_status() !== 200) throw new Exception(wp_json_encode($response->get_data()));
    update_user_option(1, 'admin_color', 'coffee', true);
    echo 'EBS_OK saved using the Site Editor global-styles REST endpoint; theme ' . wp_get_theme()->get('Name') . '; WordPress ' . $GLOBALS['wp_version'] . '; PHP ' . PHP_VERSION;
  `);
  await run('activate-after-editor-save', `
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $result = activate_plugin('easybackendstyle/easyBackendStyle.php');
    if (is_wp_error($result)) throw new Exception($result->get_error_message());
    $db = $GLOBALS['ebsPlugin']->dbc;
    $colors = $db->allColors();
    $modern = isset($colors['ebsPrimary']);
    $keys = $modern ? ['ebsPrimary','ebsSecondary','ebsTertiary'] : ['primaryColor','secondaryColor','highlight'];
    $actual = array_map(static fn($key) => $colors[$key], $keys);
    if ($actual !== ['#246b45','#24364b','#557799']) throw new Exception('Saved user colors not imported: '.wp_json_encode($actual));
    if (get_option('ebs_initial_colors') !== 'ready') throw new Exception('Colors not enabled');
    if (get_user_option('admin_color') !== ($modern ? 'personalizedcolorscheme' : 'fresh')) throw new Exception('Imported colors not selected');
    echo 'EBS_OK imported primary, secondary and tertiary: '.wp_json_encode($actual);
  `);
  await run('next-request-and-reactivation', `
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $before = $GLOBALS['ebsPlugin']->dbc->allColors();
    deactivate_plugins('easybackendstyle/easyBackendStyle.php');
    activate_plugin('easybackendstyle/easyBackendStyle.php');
    if ($GLOBALS['ebsPlugin']->dbc->allColors() !== $before) throw new Exception('Reactivation changed colors');
    echo 'EBS_OK persisted across requests and reactivation';
  `);
  // Use a real HTTP login so redirects, stylesheets and computed colors are exercised.
  const { chromium } = await import('@playwright/test');
  const browser = await chromium.launch({ headless: true,
    ...(executablePath ? { executablePath } : {}) });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    await page.goto('http://127.0.0.1:9401/wp-login.php');
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await Promise.all([page.waitForURL('**/wp-admin/**'), page.locator('#wp-submit').click()]);
    await page.goto('http://127.0.0.1:9401/wp-admin/options-general.php?page=easyBackendStyle');
    await page.locator('#colorPickForm').waitFor();
    const colors = await page.evaluate(() => {
      const root = getComputedStyle(document.documentElement);
      return {
        primary: (root.getPropertyValue('--ebsPrimary') || root.getPropertyValue('--linksColor')).trim(),
        secondary: (root.getPropertyValue('--ebsSecondary') || root.getPropertyValue('--baseMenuColor')).trim(),
        tertiary: (root.getPropertyValue('--ebsTertiary') || root.getPropertyValue('--highlightColor')).trim(),
        button: getComputedStyle(document.querySelector('#colorPickForm .button-primary')).backgroundColor,
      };
    });
    if (colors.primary !== '#246b45' || colors.secondary !== '#24364b' || colors.tertiary !== '#557799' || colors.button !== 'rgb(36, 107, 69)') {
      throw new Error('Rendered colors mismatch: ' + JSON.stringify(colors));
    }
    await page.screenshot({ path: join(root, 'test-results', `theme-colors-php-${php}.png`), fullPage: true });
    report.checks.push({ name: 'rendered-admin', colors, passed: true });
    console.log('rendered-admin: EBS_OK computed colors and primary button: ' + JSON.stringify(colors));
  } finally { await browser.close(); }
  report.passed = true;
} finally {
  mkdirSync(join(root, 'test-results'), { recursive: true });
  writeFileSync(join(root, 'test-results', `theme-colors-php-${php}.json`), JSON.stringify(report, null, 2));
  await site[Symbol.asyncDispose]();
}
