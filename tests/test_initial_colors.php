<?php
require dirname(__DIR__) . '/src/InitialColors.php';
use Farn\EasyBackendStyle\InitialColors as Colors;
function sanitize_hex_color($s) { return is_string($s) && preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $s) ? $s : null; }
function get_template_directory() { return $GLOBALS['theme']; }
function get_stylesheet_directory() { return $GLOBALS['theme']; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload = false) { $GLOBALS['options'][$key] = $value; }
function get_current_user_id() { return 1; }
function update_user_option($id, $key, $value, $global = false) { $GLOBALS['user'][$key] = $value; }
class WP_Theme_JSON_Resolver {
    static function get_merged_data() { return new self; }
    function get_settings() { return ['color'=>['palette'=>['theme'=>$GLOBALS['palette'], 'custom'=>$GLOBALS['custom_palette'] ?? [], 'default'=>$GLOBALS['default_palette'] ?? []]]]; }
}
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$GLOBALS['theme'] = sys_get_temp_dir() . '/ebs-theme-' . uniqid();
mkdir($GLOBALS['theme']);
$GLOBALS['palette'] = [];
try {
    $GLOBALS['default_palette'] = [['slug'=>'primary','color'=>'#123456'], ['slug'=>'secondary','color'=>'#345678']];
    check(Colors::detect() === [], 'Core palette must not activate colors without theme colors');
    file_put_contents($GLOBALS['theme'].'/style.css', ':root { --brand: #123456; --primary: var(--brand); --secondary: #345678; }');
    $colors = Colors::detect();
    check($colors['primary'] === '#123456' && $colors['secondary'] === '#345678', 'CSS roles and variable resolution');
    check($colors['tertiary'] === Colors::shift('#345678', 10), 'Missing tertiary lightened by 10 points');
    $GLOBALS['palette'] = [['slug'=>'primary','color'=>'#ffffff'], ['slug'=>'secondary','color'=>'#123456'], ['slug'=>'tertiary','color'=>'#ff0000']];
    $colors = Colors::detect();
    check(Colors::luminance($colors['primary']) <= Colors::luminance('#00bc6f'), 'Primary luminance cap');
    check($colors['tertiary'] === '#ff0000', 'Explicit tertiary retained');
    $GLOBALS['custom_palette'] = [['slug'=>'primary','color'=>'#246b45']];
    check(Colors::detect()['primary'] === '#246b45', 'User palette overrides theme colors');
    $GLOBALS['custom_palette'] = [];
    $GLOBALS['palette'] = [['slug'=>'primary','color'=>'#abc'], ['slug'=>'secondary','color'=>'#aabbcc']];
    check(Colors::detect() === [], 'Equivalent primary and secondary rejected');
    $GLOBALS['options'][Colors::OPTION] = 'pending';
    $db = new class {
        public $writes = 0;
        function allColors() { return []; }
        function defaults() { return ['ebsPrimary'=>'#000000','ebsSecondary'=>'#000000','ebsTertiary'=>'#000000','ebsLinks'=>'#000000']; }
        function saveColors($values) { $this->writes++; return true; }
    };
    Colors::initialize($db);
    check(!Colors::enabled() && !$db->writes, 'Invalid proposal leaves original scheme');
    $GLOBALS['palette'] = [];
    Colors::initialize($db);
    check(!$db->writes, 'Waiting installation is not reinitialized');
    $GLOBALS['options'][Colors::OPTION] = 'pending';
    Colors::initialize($db);
    check(Colors::enabled() && $db->writes === 1, 'Valid proposal saved and enabled');
    Colors::initialize($db);
    check($db->writes === 1, 'Existing colors preserved');
    check(Colors::shift('#ffffff', 10) === '#ffffff' && Colors::shift('#000000', -10) === '#000000', 'Lightness clamping');
    echo 'Initial color detection and lifecycle passed on PHP '.PHP_VERSION."\n";
} finally {
    unlink($GLOBALS['theme'].'/style.css');
    rmdir($GLOBALS['theme']);
}
