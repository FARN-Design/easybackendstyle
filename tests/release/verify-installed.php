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
$expected = get_option('ebs_ci_expected_primary', $modern ? '#096484' : '#2271b1');
if ($db->getValueFromDB($key)[0][0] !== $expected) {
    throw new RuntimeException('Stored primary color was lost on install/update');
}
if ($modern) {
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
$db->saveValueInDB('#33258a', $key);
update_option('ebs_ci_expected_primary', '#33258a');
deactivate_plugins('easybackendstyle/easyBackendStyle.php');
$result = activate_plugin('easybackendstyle/easyBackendStyle.php');
if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
if ($db->getValueFromDB($key)[0][0] !== '#33258a') throw new RuntimeException('Reactivation replaced saved colors');
echo 'EBS_PACKAGE_OK ' . PHP_VERSION . ' WP ' . $GLOBALS['wp_version'];
