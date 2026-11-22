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

    public function test_list_row_colors_have_independent_defaults_and_persist() {
        $plugin = $GLOBALS['ebsPlugin'];
        $modern = $plugin instanceof easyBackendStyle;
        if ($modern) $plugin->color_mapping();
        $db = $modern ? new \Farn\EasyBackendStyle\ebs_DatabaseConnector() : new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        $odd = $modern ? 'ebsListOdd' : 'listOddColor';
        $even = $modern ? 'ebsListEven' : 'listEvenColor';
        $this->assertTrue($db->setup_Database());
        $this->assertSame('#f6f7f7', $db->getValueFromDB($odd)[0][0]);
        $this->assertSame('#ffffff', $db->getValueFromDB($even)[0][0]);
        $this->assertTrue($db->saveColors([$odd => '#eeeeee', $even => '#ffffff']));
        $this->assertSame('#eeeeee', $db->getValueFromDB($odd)[0][0]);
        $this->assertSame('#ffffff', $db->getValueFromDB($even)[0][0]);
    }

    public function test_missing_current_menu_color_inherits_existing_highlight_once() {
        global $wpdb;
        $plugin = $GLOBALS['ebsPlugin'];
        $modern = $plugin instanceof easyBackendStyle;
        if ($modern) $plugin->color_mapping();
        $db = $modern ? new \Farn\EasyBackendStyle\ebs_DatabaseConnector() : new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        $current = $modern ? 'ebsCurrentMenuText' : 'currentMenuText';
        $highlight = $modern ? 'ebsHighlightedText' : 'highlightText';
        $this->assertTrue($db->setup_Database());
        $this->assertTrue($db->saveColors([$highlight => '#123456']));
        $wpdb->delete($db->tableName, ['Variable' => $current]);
        $db = $modern ? new \Farn\EasyBackendStyle\ebs_DatabaseConnector() : new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        $this->assertTrue($db->checkFields());
        $this->assertSame('#123456', $db->getValueFromDB($current)[0][0]);
        $this->assertTrue($db->saveColors([$current => '#abcdef', $highlight => '#ffffff']));
        $this->assertTrue($db->checkFields());
        $this->assertSame('#abcdef', $db->getValueFromDB($current)[0][0]);
        $this->assertSame('#ffffff', $db->getValueFromDB($highlight)[0][0]);
    }
}
