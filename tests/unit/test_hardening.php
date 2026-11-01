<?php
require_once dirname(__DIR__) . '/support/hardening.php';
class EBS_Hardening_Test extends WP_UnitTestCase {
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
    public function test_database_failures_and_historical_upgrade_are_recoverable() {
        if (!($GLOBALS['ebsPlugin'] instanceof easyBackendStyle)) $this->markTestSkipped('Modern migration applies to WordPress 7+');
        ebs_hardening_checks();
        $this->assertTrue(true); // The shared assertions throw on any regression.
    }
    public function test_legacy_colors_survive_batch_save_and_reactivation() {
        $p = $GLOBALS['ebsPlugin'];
        if ($p instanceof easyBackendStyle) $this->markTestSkipped('WordPress 6 compatibility branch');
        $p->dbc = new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        $this->assertTrue($p->dbc->setup_Database());
        $this->assertTrue($p->dbc->saveColors(['primaryColor'=>'#123456','menuText'=>'#abcdef']));
        $p->activate();
        $this->assertSame('#123456', $p->dbc->allColors()['primaryColor']);
    }
    public function test_frontend_is_silent_without_visible_adminbar() {
        wp_set_current_user(0);
        set_current_screen('front');
        ob_start();
        $p = $GLOBALS['ebsPlugin'];
        if ($p instanceof easyBackendStyle) { $p->ebs_root_variables_css(); $p->addStylesToFrontendAdminbar(); }
        else { $p->ebs_backend_css(); $p->frontendAdminbar(); }
        $this->assertSame('', ob_get_clean());
        $this->assertFalse(wp_style_is('ebsAdminbarFrontend', 'enqueued'));
        set_current_screen('dashboard');
    }
}
