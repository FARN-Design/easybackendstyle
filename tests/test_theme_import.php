<?php
require dirname(__DIR__).'/src/InitialColors.php';
use Farn\EasyBackendStyle\InitialColors as Colors;
function sanitize_hex_color($s) { return is_string($s) && preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $s) ? $s : null; }
function get_template_directory() { return $GLOBALS['theme']; }
function get_stylesheet_directory() { return $GLOBALS['theme']; }
class WP_Theme_JSON_Resolver {
 static function get_merged_data() { return new self; }
 function get_settings() { return ['color'=>['palette'=>$GLOBALS['palettes']]]; }
}
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
function palette($hexes) { return array_map(static fn($hex,$i)=>['slug'=>'color-'.$i,'color'=>$hex],$hexes,array_keys($hexes)); }
$GLOBALS['theme']=sys_get_temp_dir().'/ebs-import-'.uniqid(); mkdir($GLOBALS['theme']);
try {
 $GLOBALS['palettes']=['default'=>palette(['#ff0000','#00ff00'])];
 check(Colors::detect() === [], 'Core palette must not supply a scheme');
 $hexes=['#1e1b30','#33258a','#ca4963','#009bdc','#fbb800','#837ac9','#61c3b6','#f76d4b','#f5f2ed','#fbf9f7','#cccada'];
 $GLOBALS['palettes']['theme']=palette($hexes);
 $colors=Colors::detect();
 check($colors['primary']==='#33258a','Skip near-black ink and retain first suitable brand color');
 check(Colors::luminance($colors['secondary']) <= 0.06,'Dark violet menu');
 check($colors['tertiary']==='#837ac9','Prefer related violet over unrelated red');
 echo 'Perspektive: '.json_encode($colors)."\n";
 $GLOBALS['palettes']['theme']=palette(array_slice($hexes,0,5));
 check(Colors::detect()['tertiary']==='#009bdc','Prefer nearby cyan to red/yellow');
 $GLOBALS['palettes']['theme']=palette(['#33258a','#ca4963','#fbb800']);
 check(Colors::detect()['tertiary']==='#33258a','Use base violet without a related accent');
 $GLOBALS['palettes']['custom']=[['slug'=>'color-0','color'=>'#246b45']];
 check(Colors::detect()['primary']==='#246b45','Saved custom preset overrides same theme slug');
 unset($GLOBALS['palettes']['custom']);
 $GLOBALS['palettes']['theme']=palette(['#fbf9f7','#ffffff','#000000','#555555']);
 $neutral=Colors::detect();
 check(Colors::luminance($neutral['secondary'])<=0.08,'Neutral-only themes still get a dark menu');
 check($neutral['primary']!==$neutral['secondary'],'Neutral scheme roles remain distinct');
 $GLOBALS['palettes']['theme']=palette(['#fbf9f7','#ffff00','#33258a']);
 check(Colors::detect()['primary']==='#33258a','Dark chromatic color preferred over pale surfaces and bright yellow');
 $GLOBALS['palettes']['theme']=palette(['#33258a']);
 check(Colors::detect()['primary']==='#33258a','Single brand color supplies coherent tonal scheme');
 $GLOBALS['palettes']['theme']=palette(['#ffffff','#e9d5ff','#cffafe','#fce7f3','#fef3c7']);
 $pastel=Colors::detect();
 check($pastel['primary'] !== '#a2a2a2', 'Pastel-only palette must retain a chromatic hue instead of darkening white');
 check(Colors::luminance($pastel['secondary']) <= 0.08, 'Pastel menu must be dark');
 foreach (['#e9d5ff', '#ff00ff', '#00ffff', '#ff3300', '#ffff00'] as $intense) {
  $GLOBALS['palettes']['theme']=palette([$intense]);
  $muted=Colors::detect()['secondary'];
  $rgb=array_map(static fn($c)=>hexdec($c)/255,str_split(substr($muted,1),2));
  $light=(max($rgb)+min($rgb))/2;
  $sat=$light>0 ? (max($rgb)-min($rgb))/(1-abs(2*$light-1)) : 0;
  check($light <= 0.205 && $sat <= 0.56, 'Large menu surface must be dark and muted: '.$intense);
 }
 $GLOBALS['palettes']['theme']=palette(['#254b80','#26303d']);
 check(Colors::detect()['secondary']==='#26303d', 'Prefer an existing calm related dark theme color');
 // Complete browser fixture: the darker menu makes this tonal accent eligible.
 $GLOBALS['palettes']=['theme'=>palette(['#5744b8','#4633a2','#362490','#7bf1b2'])];
 check(Colors::detect()===['primary'=>'#5744b8','secondary'=>'#241b4b','tertiary'=>'#4633a2'], 'Browser fixture must prefer the eligible related violet');
 $GLOBALS['palettes']=['theme'=>palette(['#246b45','#24364b','#557799','#7bf1b2'])];
 check(Colors::detect()===['primary'=>'#246b45','secondary'=>'#113220','tertiary'=>'#557799'], 'Changed browser palette must retain its expected accent');
 $GLOBALS['palettes']['theme']=palette(['invalid']);
 file_put_contents($GLOBALS['theme'].'/style.css',':root { --brand: #246b45; --primary: var(--brand); --secondary: #fff; }');
 check(Colors::detect()['primary']==='#246b45','CSS variable fallback resolves local declarations');
 echo 'Import regressions passed on PHP '.PHP_VERSION."\n";
} finally { @unlink($GLOBALS['theme'].'/style.css'); rmdir($GLOBALS['theme']); }
