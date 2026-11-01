<?php
/** Real WordPress/database tests, also exercised on the minimum native PHP. */
class EBS_Settings_Test extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
        // SHOW TABLES cannot see WP's temporary-table substitutions. Use real tables
        // in this disposable database and remove them in tear_down.
        remove_filter('query', [$this, '_create_temporary_tables']);
        remove_filter('query', [$this, '_drop_temporary_tables']);
    }
    public function tear_down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ebs_easyBackendStyle");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}easyBackendStyle");
        parent::tear_down();
    }
    public function test_existing_colors_survive_database_setup_and_reactivation() {
        $plugin = $GLOBALS['ebsPlugin'];
        $db = $plugin->dbc = $plugin instanceof easyBackendStyle ? new \Farn\EasyBackendStyle\ebs_DatabaseConnector() : new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        if ($plugin instanceof easyBackendStyle) $plugin->color_mapping();
        $db->setup_Database();
        $key = $plugin instanceof easyBackendStyle ? 'ebsPrimary' : 'primaryColor';
        $db->saveValueInDB('#33258a', $key);
        $db->setup_Database();
        $db->checkFields();
        $plugin->activate();
        $this->assertSame('#33258a', $db->getValueFromDB($key)[0][0]);
    }

    public function test_settings_stay_under_wordpress_settings() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $GLOBALS['ebsPlugin']->sub_settings_page();
        $this->assertSame('options-general.php', $GLOBALS['_parent_pages']['easyBackendStyle']);
    }

    public function test_both_german_locales_have_complete_bundled_catalogs() {
        foreach (['de_DE', 'de_DE_formal'] as $locale) {
            $catalog = require EBS_PLUGIN_PATH . '/src/languages/easybackendstyle-' . $locale . '.l10n.php';
            $this->assertNotEmpty($catalog['messages']['Documentation']);
            $this->assertStringContainsString('farnlabs.de', $catalog['messages']['https://www.farnlabs.com/easy-backend-style/documentation']);
        }
    }
}
