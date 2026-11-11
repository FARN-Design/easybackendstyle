import { runCLI } from '@wp-playground/cli';
import { chromium } from '@playwright/test';
import { resolve, join } from 'node:path';
import { mkdirSync, writeFileSync } from 'node:fs';
const root = resolve(import.meta.dirname, '..');
const php = process.env.FARN_TEST_PHP_VERSION || '8.5';
const executablePath = process.argv.find(a => a.startsWith('--chromium='))?.slice(11);
const site = await runCLI({ command:'server', php, wp:'latest', port:9402, quiet:true,
  mount:[{hostPath:resolve(process.argv[2]),vfsPath:'/artifacts/plugin.zip'}],
  blueprint:{steps:[
    {step:'defineWpConfigConsts',consts:{DISABLE_WP_CRON:true}},
    {step:'runPHP',code:`<?php require '/wordpress/wp-load.php';
      $dir=get_theme_root().'/ebs-no-colors'; wp_mkdir_p($dir);
      file_put_contents($dir.'/style.css','/* Theme Name: No palette */');
      file_put_contents($dir.'/index.php','<?php // Test fixture');
      wp_clean_themes_cache(); switch_theme('ebs-no-colors');
      update_user_option(1,'admin_color','modern',true);`},
    {step:'installPlugin',pluginData:{resource:'vfs',path:'/artifacts/plugin.zip'},options:{activate:true}},
    ...(process.argv.includes('--upgrade-fixture') ? [{step:'runPHP',code:`<?php require '/wordpress/wp-load.php';
      $GLOBALS['ebsPlugin']->color_mapping();
      $db=$GLOBALS['ebsPlugin']->dbc;
      if (!$db->saveColors(['ebsPrimary'=>'#2271b1','ebsSecondary'=>'#1d2327','ebsTertiary'=>'#2271b1'])) throw new Exception('Could not seed old defaults');
      $GLOBALS['wpdb']->delete($db->tableName, ['Variable'=>'ebsMenuHover']);
      update_option('ebs_initial_colors','waiting');`}] : []),
  ]}});
let browser;
try {
  browser = await chromium.launch({headless:true,...(executablePath ? {executablePath} : {})});
  const page=await browser.newPage({viewport:{width:1440,height:1000}});
  await page.goto('http://127.0.0.1:9402/wp-login.php');
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('password');
  await Promise.all([page.waitForURL('**/wp-admin/**'),page.locator('#wp-submit').evaluate(el => el.click())]);
  await page.goto('http://127.0.0.1:9402/wp-admin/options-general.php?page=easyBackendStyle');
  for (const [key,value] of Object.entries({ebsPrimary:'#3858e9',ebsSecondary:'#1e1e1e',ebsTertiary:'#3858e9'})) {
    if (await page.locator('#'+key).inputValue() !== value) throw Error('Wrong default '+key);
  }
  if (await page.getByText(/Please select.*admin color scheme/).count()) throw Error('Obsolete profile warning');
  // Compare the actual Modern UI, not values copied from our implementation.
  const snapshot = async () => {
    await page.mouse.move(1300, 50);
    return page.evaluate(() => {
      const samples = {
        background:['body','backgroundColor'], menu:['#adminmenu','backgroundColor'],
        toolbar:['#wpadminbar','backgroundColor'], submenu:['#menu-settings .wp-submenu','backgroundColor'],
        active:['#menu-settings > a','backgroundColor'], submenuText:['#menu-settings .wp-submenu li:not(.current) a','color'],
        button:['#submit','backgroundColor'], buttonText:['#submit','color'], link:['.ebs-documentation','color'],
      };
      return Object.fromEntries(Object.entries(samples).map(([key,[selector,property]])=>[key,getComputedStyle(document.querySelector(selector))[property]]));
    });
  };
  const before = await snapshot();
  await Promise.all([page.waitForNavigation(),page.locator('#submit').evaluate(el => el.click())]);
  const after = await snapshot();
  for (const key of Object.keys(before)) {
    const a=before[key].match(/[\d.]+/g).map(Number), b=after[key].match(/[\d.]+/g).map(Number);
    if (a.length!==b.length || a.some((n,i)=>Math.abs(n-b[i])>1)) throw Error(`Unchanged save changed ${key}: ${before[key]} -> ${after[key]}`);
  }
  console.log('Modern UI before/after unchanged save: '+JSON.stringify({before,after}));
  // Editing one advanced field must not recalculate the other stored values.
  const fields = () => page.locator('#colorPickForm input[type=text]').evaluateAll(nodes=>Object.fromEntries(nodes.map(n=>[n.name,n.value])));
  await page.reload();
  await page.evaluate(() => { jQuery.fx.off = true; });
  const previousFields = await fields();
  await page.locator('.ebs_advanced_settings_toggle').evaluate(el => el.click());
  const deletePicker=page.locator('.wrapper_ebsDeleteLinks');
  await deletePicker.locator('button.wp-color-result').evaluate(el => el.click());
  await deletePicker.locator('input#ebsDeleteLinks').fill('#aa1122');
  await deletePicker.locator('input#ebsDeleteLinks').press('Tab');
  await deletePicker.locator('button.wp-color-result').evaluate(el => el.click());
  await Promise.all([page.waitForNavigation(),page.locator('#submit').evaluate(el => el.click())]);
  const savedFields = await fields();
  for (const [key,value] of Object.entries(previousFields)) {
    if (savedFields[key] !== (key==='ebsDeleteLinks' ? '#aa1122' : value)) throw Error('Single-field save changed '+key);
  }
  const picker=page.locator('.wrapper_ebsPrimary');
  await picker.locator('button.wp-color-result').evaluate(el => el.click());
  await picker.locator('input#ebsPrimary').fill('#33258a');
  await picker.locator('input#ebsPrimary').press('Tab');
  await picker.locator('button.wp-color-result').evaluate(el => el.click());
  await Promise.all([page.waitForNavigation(),page.locator('#submit').evaluate(el => el.click())]);
  await page.getByText('Changes saved.',{exact:true}).waitFor();
  await page.mouse.move(1300, 50);
  const actual=await page.locator('#submit').evaluate(el=>getComputedStyle(el).backgroundColor);
  if (actual !== 'rgb(51, 37, 138)') throw Error('First save did not immediately apply CSS: '+actual);
  if (!await page.locator('body.admin-color-personalizedcolorscheme').count()) throw Error('Wrong body color scheme after first save');
  await page.reload();
  if (await page.locator('#ebsPrimary').inputValue() !== '#33258a') throw Error('Saved color lost');
  mkdirSync(join(root,'test-results'),{recursive:true});
  writeFileSync(join(root,`test-results/default-colors-${php}.json`),JSON.stringify({passed:true,php,before,after,singleFieldEdit:true,customizedButton:actual},null,2));
  console.log(`PHP ${php}: Modern before/after comparison, single-field edits, immediate save CSS and persistence passed`);
} finally { if(browser) await browser.close(); await site[Symbol.asyncDispose](); }
