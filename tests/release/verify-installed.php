<?php
define('WP_ADMIN', true);
require '/wordpress/wp-load.php';
wp_set_current_user(1);
$GLOBALS['ebsPlugin']->initialize();
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
$plugin = $GLOBALS['ebsPlugin'];
$db = $plugin->dbc;
$db->checkFields();
if (!is_plugin_active('easybackendstyle/easyBackendStyle.php')) {
    throw new RuntimeException('Plugin is not active');
}
$modern = $plugin instanceof easyBackendStyle;
$key = $modern ? 'ebsPrimary' : 'primaryColor';
$expected = get_option('ebs_ci_expected_primary', $modern ? '#3858e9' : '#2271b1');
if ($db->getValueFromDB($key)[0][0] !== $expected) {
    throw new RuntimeException('Stored primary color was lost on install/update');
}
// A fresh installation without a theme palette must preserve the user's scheme.
$fresh = get_option('ebs_ci_fresh') && !get_option('ebs_ci_expected_primary');
if ($fresh) {
    if (\Farn\EasyBackendStyle\InitialColors::enabled() || get_user_option('admin_color') !== 'coffee') {
        throw new RuntimeException('Fresh installation replaced the original admin scheme');
    }
    ob_start();
    if ($modern) $plugin->ebs_root_variables_css(); else $plugin->ebs_backend_css();
    if (ob_get_clean() !== '') throw new RuntimeException('Fresh installation emitted unconfirmed colors');
} elseif ($modern) {
    ob_start(); $plugin->ebs_root_variables_css(); $css = ob_get_clean();
    if (!str_contains($css, '--ebsPrimary: ' . $expected . ';')) {
        throw new RuntimeException('Saved primary color missing from rendered CSS');
    }
}
wp_set_current_user(1);
foreach (['en_US', 'de_DE', 'de_DE_formal'] as $locale) {
    $catalog = EBS_PLUGIN_PATH . '/src/languages/easybackendstyle-' . $locale . '.mo';
    unload_textdomain('easybackendstyle', true);
    if ($locale !== 'en_US' && !load_textdomain('easybackendstyle', $catalog, $locale)) {
        throw new RuntimeException('Failed to load ' . $locale);
    }
    ob_start(); \Farn\EasyBackendStyle\AdminSettings::header(); $html = ob_get_clean();
    $domain = $locale === 'en_US' ? 'farnlabs.com' : 'farnlabs.de';
    if (!str_contains($html, $domain)) throw new RuntimeException('Wrong documentation locale');
}
// Exercise the actual authorized save handler before expecting plugin CSS.
$values = $db->allColors();
$values[$key] = '#33258a';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $values + ['submit'=>'', 'ebs_nonce'=>wp_create_nonce('ebs_save_colors')];
$_REQUEST = $_POST;
\Farn\EasyBackendStyle\AdminSettings::handle($db, $db->defaults());
if (!\Farn\EasyBackendStyle\InitialColors::enabled()) throw new RuntimeException('Save did not enable colors');
if (get_user_option('admin_color') !== ($modern ? 'personalizedcolorscheme' : 'fresh')) throw new RuntimeException('Save did not select plugin scheme');
ob_start();
if ($modern) $plugin->ebs_root_variables_css(); else $plugin->ebs_backend_css();
$css = ob_get_clean();
if (!str_contains($css, '#33258a')) throw new RuntimeException('Saved primary missing from CSS after explicit save');
update_option('ebs_ci_expected_primary', '#33258a');
deactivate_plugins('easybackendstyle/easyBackendStyle.php');
$result = activate_plugin('easybackendstyle/easyBackendStyle.php');
if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
if ($db->getValueFromDB($key)[0][0] !== '#33258a') throw new RuntimeException('Reactivation replaced saved colors');
echo 'EBS_PACKAGE_OK ' . PHP_VERSION . ' WP ' . $GLOBALS['wp_version'];
