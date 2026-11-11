import {runCLI} from '@wp-playground/cli';
import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {resolve,join} from 'node:path';
const root=resolve(import.meta.dirname,'..');
const php=process.env.FARN_TEST_PHP_VERSION||'8.5';
const wp=process.env.FARN_TEST_WP_VERSION||'latest';
const zip=resolve(process.argv[2]);
const legacy=process.argv.includes('--legacy');
mkdirSync(join(root,'test-results'),{recursive:true});
const site=await runCLI({command:'server',php,wp,port:9400,quiet:true,blueprint:{preferredVersions:{php,wp},steps:[
 {step:'defineWpConfigConsts',consts:{SAVEQUERIES:true,DISABLE_WP_CRON:true}},
 {step:'installPlugin',pluginData:{resource:'vfs',path:'/artifacts/easybackendstyle.zip'},options:{activate:true}},
]},mount:[{hostPath:zip,vfsPath:'/artifacts/easybackendstyle.zip'}]});
const reports=[];
async function run(name,code) {
 const r=await site.playground.run({code});
 reports.push({name,exitCode:r.exitCode,text:r.text,errors:r.errors});
 if(r.exitCode!==0||!r.text.includes('EBS_OK'))throw Error(name+': '+r.text+' '+r.errors);
 console.log(`${name}: ${r.text}`);
}
try {
 await run('guest',`<?php require '/wordpress/wp-load.php';
 global $wpdb; wp_set_current_user(0);
 ob_start(); do_action('wp_head'); $head=ob_get_clean();
 $own=array_filter($wpdb->queries,static fn($q)=>stripos($q[0],'easyBackendStyle')!==false);
 if(count($own)||str_contains($head,'ebs-root')||wp_style_is('ebsAdminbarFrontend','enqueued')) throw new RuntimeException('Guest request ran color SQL or emitted CSS');
 echo 'EBS_OK guest: zero color SQL and zero plugin CSS; PHP '.PHP_VERSION.' WP '.$GLOBALS['wp_version'];`);
 await run('adminbar',`<?php require '/wordpress/wp-load.php'; wp_set_current_user(1); show_admin_bar(true);
 \\Farn\\EasyBackendStyle\\InitialColors::saved();
 global $wpdb; $start=count($wpdb->queries);
 $p=$GLOBALS['ebsPlugin'];
 if(method_exists($p,'frontendAdminbar')) $p->frontendAdminbar(); else $p->addStylesToFrontendAdminbar();
 $css=implode('',wp_styles()->get_data('ebsAdminbarFrontend','after') ?: []);
 if(!wp_style_is('ebsAdminbarFrontend','enqueued') || !str_contains($css,'#wpadminbar {') || str_contains($css,':root')) throw new RuntimeException('Adminbar CSS must be isolated');
 $own=array_filter(array_slice($wpdb->queries,$start),static fn($q)=>stripos($q[0],'easyBackendStyle')!==false);
 if(count($own)>2) throw new RuntimeException('Colors were not loaded in bulk');
 wp_dequeue_style('ebsAdminbarFrontend'); show_admin_bar(false);
 if(method_exists($p,'frontendAdminbar')) $p->frontendAdminbar(); else $p->addStylesToFrontendAdminbar();
 if(wp_style_is('ebsAdminbarFrontend','enqueued')) throw new RuntimeException('Hidden adminbar loaded CSS');
 echo 'EBS_OK visible/hidden adminbar; '.count($own).' color queries';`);
 if(!legacy) {
 const suite=readFileSync(join(root,'tests/support/hardening.php'),'utf8').replace(/^<\?php/,'');
 await run('database-failures',"<?php require '/wordpress/wp-load.php'; wp_set_current_user(1);\n"+suite+"\nebs_hardening_checks(); echo 'EBS_OK migrations, backup, retry, missing fields, batch failure, denied CREATE';");
 } else {
 await run('legacy-save',`<?php require '/wordpress/wp-load.php'; $db=$GLOBALS['ebsPlugin']->dbc;
 if(!$db->saveColors(['primaryColor'=>'#123456','menuText'=>'#abcdef'])) throw new RuntimeException('Legacy save failed');
 $GLOBALS['ebsPlugin']->activate(); if($db->allColors()['primaryColor']!=='#123456')throw new RuntimeException('Legacy reactivation lost colors');
 echo 'EBS_OK legacy save/reactivation';`);
 await run('legacy-missing-table',`<?php define('WP_ADMIN',true); require '/wordpress/wp-load.php';
 require_once ABSPATH . 'wp-admin/includes/template.php'; wp_set_current_user(1);
 $GLOBALS['ebsPlugin']->dbc = new \\Farn\\EasyBackendStyle\\deprecated\\ebs_DatabaseConnector();
 $GLOBALS['ebsPlugin']->dbc->tableName = $GLOBALS['wpdb']->prefix.'ebs_test_missing';
 set_error_handler(static function($level,$message,$file,$line) { if(str_contains($file,'easybackendstyle')) throw new RuntimeException($message); return false; });
 ob_start(); include EBS_PLUGIN_PATH.'src/deprecated/ebs_SettingsSubMenu.php'; $html=ob_get_clean(); restore_error_handler();
 if(!str_contains($html,'value="#2271b1"') || !str_contains($html,'setting-error-database')) throw new RuntimeException('Missing database must render safe defaults and a notice');
 echo 'EBS_OK legacy missing table: safe defaults and visible error';`);

 }
} finally {
 writeFileSync(join(root,'test-results',`hardening-${wp}-php-${php}.json`),JSON.stringify(reports,null,2));
 await site[Symbol.asyncDispose]();
}
