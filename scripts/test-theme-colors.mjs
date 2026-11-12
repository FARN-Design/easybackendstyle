import { runCLI } from '@wp-playground/cli';
import { chromium } from '@playwright/test';
import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, join } from 'node:path';
const root=resolve(import.meta.dirname,'..');
const argument=name=>process.argv.find(v=>v.startsWith(name+'='))?.slice(name.length+1);
const php=argument('--php')||process.env.FARN_TEST_PHP_VERSION||'8.5';
const wp=argument('--wp')||process.env.FARN_TEST_WP_VERSION||'latest';
const theme=argument('--theme')||'twentytwentyfive';
const executablePath=argument('--chromium')||process.env.EBS_CHROMIUM_PATH;
const archive=resolve(process.argv[2]);
const report={php,wp,theme,checks:[]};
mkdirSync(join(root,'test-results'),{recursive:true});
const site=await runCLI({command:'server',php,wp,port:9401,quiet:true,
  blueprint:{steps:[
    {step:'defineWpConfigConsts',consts:{DISABLE_WP_CRON:true}},
    {step:'installTheme',themeData:{resource:'wordpress.org/themes',slug:theme},options:{activate:true}},
    {step:'installPlugin',pluginData:{resource:'vfs',path:'/artifacts/plugin.zip'},options:{activate:false}},
  ]},mount:[{hostPath:archive,vfsPath:'/artifacts/plugin.zip'}]});
async function run(name,code) {
 const r=await site.playground.run({code:`<?php require '/wordpress/wp-load.php'; wp_set_current_user(1); ${code}`});
 report.checks.push({name,text:r.text,errors:r.errors});
 if(r.exitCode!==0||!r.text.includes('EBS_OK')) throw Error(name+': '+r.text+r.errors);
 console.log(name+': '+r.text);
}
async function palette(primary,secondary,tertiary) {
 const slugs=theme==='twentytwentythree' ? ['primary','secondary','tertiary'] : ['accent-1','base','accent-2'];
 await run('save-site-editor-palette',`
  $id=WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
  $request=new WP_REST_Request('POST','/wp/v2/global-styles/'.$id);
  $request->set_param('settings',['color'=>['palette'=>['theme'=>[
   ['slug'=>'${slugs[0]}','name'=>'Accent','color'=>'${primary}'],
   ['slug'=>'${slugs[1]}','name'=>'Base','color'=>'${secondary}'],
   ['slug'=>'${slugs[2]}','name'=>'Accent 2','color'=>'${tertiary}'],
   ['slug'=>'contrast','name'=>'Contrast','color'=>'#7bf1b2']
  ]]]]);
  $response=rest_do_request($request);
  if($response->get_status()!==200) throw new Exception(wp_json_encode($response->get_data()));
  echo 'EBS_OK Global Styles saved for '.wp_get_theme()->get('Name');`);
}
let browser;
try {
 await palette('#5744b8','#4633a2','#362490');
 await run('activation-is-inert',`
  require_once ABSPATH.'wp-admin/includes/plugin.php';
  update_user_option(1,'admin_color','coffee',true);
  $r=activate_plugin('easybackendstyle/easyBackendStyle.php');
  if(is_wp_error($r)) throw new Exception($r->get_error_message());
  if(get_user_option('admin_color')!=='coffee'||get_option('ebs_initial_colors')!=='waiting') throw new Exception('Activation changed scheme');
  $colors=$GLOBALS['ebsPlugin']->dbc->allColors();
  if($colors['ebsPrimary']!=='#3858e9') throw new Exception('Activation imported theme colors');
  update_option('ebs_ci_before_import',$colors);
  echo 'EBS_OK unchanged scheme and defaults; PHP '.PHP_VERSION.' WP '.$GLOBALS['wp_version'];`);
 browser=await chromium.launch({headless:true,...(executablePath?{executablePath}:{})});
 const page=await browser.newPage();
 await page.goto('http://127.0.0.1:9401/wp-login.php');
 await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('password');
 await Promise.all([page.waitForURL('**/wp-admin/**'),page.locator('#wp-submit').evaluate(el=>el.click())]);
 await page.goto('http://127.0.0.1:9401/wp-admin/options-general.php?page=easyBackendStyle');
 await page.locator('#ebs-import-theme-colors').waitFor();
 const before=await page.locator('#adminmenu').evaluate(el=>getComputedStyle(el).backgroundColor);
 const denied=await page.request.post('http://127.0.0.1:9401/wp-admin/admin-ajax.php',{form:{action:'ebs_import_theme_colors'}});
 if(denied.status()!==403) throw Error('Import accepted missing nonce');
 async function clickImport(expected) {
  await page.locator('#ebs-import-theme-colors').evaluate(el=>el.click());
  await page.waitForFunction(value=>document.querySelector('#ebsPrimary').value===value,expected[0]);
  await page.waitForFunction(()=>!document.querySelector('#ebs-import-theme-colors').disabled);
  for(const [index,key] of ['ebsPrimary','ebsSecondary','ebsTertiary'].entries()) {
   const actual = await page.locator('#'+key).inputValue();
   if(actual!==expected[index]) throw Error(`Wrong imported ${key}: expected ${expected[index]}, received ${actual} (PHP ${php}, theme ${theme})`);
  }
  const rgb = expected[2].slice(1).match(/../g).map(v=>parseInt(v,16));
  const expectedHighlight = `rgb(${rgb.join(', ')})`;
  await page.waitForFunction(expected => getComputedStyle(document.querySelector('#menu-settings > a')).backgroundColor === expected, expectedHighlight);
  const preview = await page.locator('html').evaluate(el=>getComputedStyle(el).getPropertyValue('--ebsTertiary').trim());
  if(preview !== expected[2]) throw Error('Import preview retained previous tertiary color');
 }
 await clickImport(['#5744b8','#241b4b','#4633a2']);
 // Live preview changes this page; persistence is checked separately below.
 await run('import-is-not-a-save',`
  if($GLOBALS['ebsPlugin']->dbc->allColors()!==get_option('ebs_ci_before_import')||get_user_option('admin_color')!=='coffee') throw new Exception('Import persisted colors');
  echo 'EBS_OK form import has no stored side effects';`);
 // Change the editor palette while the settings page is already open.
 await palette('#246b45','#24364b','#557799');
 await clickImport(['#246b45','#113220','#557799']);
 await Promise.all([page.waitForNavigation(),page.locator('#submit').evaluate(el=>el.click())]);
 await page.mouse.move(1200,20);
 const button=await page.locator('#submit').evaluate(el=>getComputedStyle(el).backgroundColor);
 if(button!=='rgb(36, 107, 69)') throw Error('Saved import CSS mismatch: '+button);
 const savedHighlight = await page.locator('#menu-settings > a').evaluate(el=>getComputedStyle(el).backgroundColor);
 if(savedHighlight !== 'rgb(85, 119, 153)') throw Error('Saved active menu does not use tertiary: '+savedHighlight);
 await run('save-and-reactivation',`
  require_once ABSPATH.'wp-admin/includes/plugin.php';
  $before=$GLOBALS['ebsPlugin']->dbc->allColors();
  if($before['ebsPrimary']!=='#246b45'||$before['ebsSecondary']!=='#113220'||$before['ebsTertiary']!=='#557799') throw new Exception('Save lost imported values');
  update_user_option(1,'admin_color','coffee',true);
  deactivate_plugins('easybackendstyle/easyBackendStyle.php'); activate_plugin('easybackendstyle/easyBackendStyle.php');
  if(get_user_option('admin_color')!=='coffee'||$GLOBALS['ebsPlugin']->dbc->allColors()!==$before) throw new Exception('Reactivation changed chosen scheme or colors');
  echo 'EBS_OK saved imports and inert reactivation';`);
 report.passed=true;
 console.log('EBS_OK current Site Editor palette, read-only import, nonce, explicit save and unchanged activation');
} finally {
 writeFileSync(join(root,`test-results/theme-colors-${theme}-php-${php}.json`),JSON.stringify(report,null,2));
 if(browser) await browser.close(); await site[Symbol.asyncDispose]();
}
