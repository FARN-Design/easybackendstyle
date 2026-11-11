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
      update_user_option(1,'admin_color','fresh',true);`},
    {step:'installPlugin',pluginData:{resource:'vfs',path:'/artifacts/plugin.zip'},options:{activate:true}},
  ]}});
let browser;
try {
  browser = await chromium.launch({headless:true,...(executablePath ? {executablePath} : {})});
  const page=await browser.newPage({viewport:{width:1440,height:1000}});
  await page.goto('http://127.0.0.1:9402/wp-login.php');
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('password');
  await Promise.all([page.waitForURL('**/wp-admin/**'),page.locator('#wp-submit').click()]);
  await page.goto('http://127.0.0.1:9402/wp-admin/options-general.php?page=easyBackendStyle');
  for (const [key,value] of Object.entries({ebsPrimary:'#2271b1',ebsSecondary:'#1d2327',ebsTertiary:'#2271b1'})) {
    if (await page.locator('#'+key).inputValue() !== value) throw Error('Wrong default '+key);
  }
  if (await page.getByText(/Please select.*admin color scheme/).count()) throw Error('Obsolete profile warning');
  const picker=page.locator('.wrapper_ebsPrimary');
  await picker.locator('button.wp-color-result').click();
  await picker.locator('input#ebsPrimary').fill('#33258a');
  await picker.locator('input#ebsPrimary').press('Tab');
  await picker.locator('button.wp-color-result').click();
  await Promise.all([page.waitForNavigation(),page.locator('#submit').click()]);
  await page.getByText('Changes saved.',{exact:true}).waitFor();
  await page.mouse.move(1300, 50);
  const actual=await page.locator('#submit').evaluate(el=>getComputedStyle(el).backgroundColor);
  if (actual !== 'rgb(51, 37, 138)') throw Error('First save did not immediately apply CSS: '+actual);
  if (!await page.locator('body.admin-color-personalizedcolorscheme').count()) throw Error('Wrong body color scheme after first save');
  await page.reload();
  if (await page.locator('#ebsPrimary').inputValue() !== '#33258a') throw Error('Saved color lost');
  mkdirSync(join(root,'test-results'),{recursive:true});
  writeFileSync(join(root,`test-results/default-colors-${php}.json`),JSON.stringify({passed:true,php,firstSaveButton:actual}));
  console.log(`PHP ${php}: WordPress defaults, no profile warning, immediate first-save CSS and persistence passed`);
} finally { if(browser) await browser.close(); await site[Symbol.asyncDispose](); }
