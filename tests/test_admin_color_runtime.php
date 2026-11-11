<?php
function is_admin() { return true; }
function get_user_option($key) { return "personalizedcolorscheme"; }
function __($s, $domain = null) { return $s; }
function sanitize_hex_color($value) { return is_string($value) && preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $value) ? $value : null; }
$source = file_get_contents(dirname(__DIR__) . '/easyBackendStyle.php');
eval(substr($source, strpos($source, 'class easyBackendStyle {')));
class TestColors extends easyBackendStyle {
    public $values = [];
    function __construct() {}
    function getColor($name) { return $this->values[$name] ?? null; }
}
$p = new TestColors();
$p->color_mapping();
foreach ([[], ['ebsPrimary'=>'#33258a','ebsSecondary'=>'#160038'], ['ebsPrimary'=>'#abc'], ['ebsPrimary'=>'</style>']] as $case) {
    $p->values = $case;
    ob_start(); $p->ebs_root_variables_css(); $css = ob_get_clean();
    $expected = isset($case['ebsPrimary']) && sanitize_hex_color($case['ebsPrimary']) ? $case['ebsPrimary'] : '#2271b1';
    if (strpos($css, '--ebsPrimary: '.$expected.';') === false) throw new Exception('Stored/default color mismatch');
    if (substr_count($css, '</style>') !== 1) throw new Exception('Unsafe output');
    if ($expected === '#33258a' && strpos($css, '--ebsPrimaryRGB: 51, 37, 138;') === false) throw new Exception('RGB mismatch');
    if ($expected === '#abc' && strpos($css, '--ebsPrimaryRGB: 170, 187, 204;') === false) throw new Exception('Short hex mismatch');
}
echo 'Saved colors, defaults, RGB and invalid-value fallback pass on PHP '.PHP_VERSION."\n";
