import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { resolve, join } from 'node:path';
import { runCLI } from '@wp-playground/cli';
const root = resolve(import.meta.dirname, '..');
const php = process.env.FARN_TEST_PHP_VERSION || '8.5';
const wp = process.env.FARN_TEST_WP_VERSION || 'latest';
if (!['8.2', '8.5'].includes(php)) throw new Error('Test minimum or Blueprint PHP');
if (!/^(latest|\d+\.\d+(?:\.\d+)?)$/.test(wp)) throw new Error('Invalid WordPress');
const archive = resolve(process.argv[2] || 'output/easybackendstyle.zip');
const preview = JSON.parse(readFileSync(join(root, 'blueprint.json'), 'utf8'));
const publicPreview = JSON.parse(readFileSync(join(root, '.wordpress-org/blueprints/blueprint.json'), 'utf8'));
const verify = readFileSync(join(root, 'tests/release/verify-installed.php'), 'utf8');
mkdirSync(join(root, 'test-results'), { recursive: true });
const install = {step:'installPlugin', pluginData:{resource:'vfs',path:'/artifacts/easybackendstyle.zip'},options:{activate:false}};
const scenario = process.argv[3];
if (!['fresh', 'existing', 'preview'].includes(scenario)) throw new Error('Invalid package scenario');
{
  const steps = [{step:'defineWpConfigConsts',consts:{DISABLE_WP_CRON:true}}];
  if (scenario === 'fresh') steps.push({step:'runPHP',code:`<?php require '/wordpress/wp-load.php';
    $dir = get_theme_root() . '/ebs-no-colors'; wp_mkdir_p($dir);
    file_put_contents($dir . '/style.css', "/* Theme Name: EBS no-color test fixture */");
    file_put_contents($dir . '/index.php', '<?php // No theme palette.');
    wp_clean_themes_cache(); switch_theme('ebs-no-colors');
    update_user_option(1, 'admin_color', 'coffee', true);
    update_option('ebs_ci_fresh', true);`});
  if (scenario === 'existing') steps.push({step:'runPHP',code:`<?php require '/wordpress/wp-load.php';
    global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'ebs_easyBackendStyle';
    dbDelta("CREATE TABLE $table (id mediumint(9) NOT NULL AUTO_INCREMENT, Variable varchar(128) NOT NULL UNIQUE, Value varchar(128) NOT NULL, UNIQUE KEY id (id)) " . $wpdb->get_charset_collate() . ';');
    $wpdb->insert($table, ['Variable'=>'ebsPrimary', 'Value'=>'#33258a']);
    update_option('ebs_ci_expected_primary', '#33258a');`});
  if (scenario === 'preview') {
    steps.push(...publicPreview.steps.map(step => step.step === 'installPlugin' ? {...step, pluginData:install.pluginData} : step));
    steps.push({step:'runPHP',code:"<?php require '/wordpress/wp-load.php'; update_option('ebs_ci_expected_primary','#00ff00');"});
  } else {
    steps.push(install, ...preview.steps.filter(step => step.step !== 'login'));
  }
  const blueprint = {...(scenario === 'preview' ? publicPreview : preview), preferredVersions:{php,wp}, steps};
  process.stdout.write(`Testing ${scenario} on PHP ${php} / WordPress ${wp}...\n`);
  const report = join(root, 'test-results', `package-${scenario}-php-${php}.json`);
  const site = await runCLI({command:'server', php, wp, port:9400, quiet:true, blueprint,
    mount:[{hostPath:archive,vfsPath:'/artifacts/easybackendstyle.zip'}]});
  try {
    const runtime = await site.playground.run({code:"<?php require '/wordpress/wp-load.php'; echo json_encode(['php'=>PHP_VERSION,'wordpress'=>$GLOBALS['wp_version']]);"});
    if (runtime.exitCode !== 0 || !runtime.text) throw new Error('Runtime verification failed: '+JSON.stringify(runtime));
    const versions = JSON.parse(runtime.text);
    if (!versions.php.startsWith(php+'.') || (wp !== 'latest' && versions.wordpress !== wp)) throw new Error('Wrong runtime: '+runtime.text);
    const requests = [];
    for (let request = 0; request < 2; request++) {
      const result = await site.playground.run({code:verify});
      requests.push(result);
      if (result.exitCode !== 0 || !result.text.includes('EBS_PACKAGE_OK')) throw new Error('Package assertions failed: '+JSON.stringify(result));
    }
    writeFileSync(report, JSON.stringify({...versions, scenario, passed:true, requests}, null, 2));
    process.stdout.write(`PHP ${php}, WordPress ${versions.wordpress}, ${scenario}: passed\n`);
  } catch (error) {
    writeFileSync(report, JSON.stringify({php,wp,scenario,passed:false,error:String(error)},null,2));
    console.error(String(error));
    process.exitCode = 1;
  } finally { await site[Symbol.asyncDispose](); }
}
