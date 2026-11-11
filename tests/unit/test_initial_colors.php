<?php
/** Lifecycle checks against the disposable WordPress database. */
class EBS_Initial_Colors_Test extends WP_UnitTestCase {
    public function test_fresh_install_waits_but_existing_install_is_preserved() {
        global $wpdb;
        $modern = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
        $legacy = new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
        $wpdb->query("DROP TABLE IF EXISTS {$modern->tableName}");
        $wpdb->query("DROP TABLE IF EXISTS {$legacy->tableName}");
        delete_option(\Farn\EasyBackendStyle\InitialColors::OPTION);
        \Farn\EasyBackendStyle\InitialColors::prepare();
        $this->assertFalse(\Farn\EasyBackendStyle\InitialColors::enabled());
        $this->assertSame('pending', get_option(\Farn\EasyBackendStyle\InitialColors::OPTION));
        // An existing table is the upgrade signal even when no marker was stored yet.
        remove_filter('query', [$this, '_create_temporary_tables']);
        $this->assertTrue($legacy->setup_Database());
        delete_option(\Farn\EasyBackendStyle\InitialColors::OPTION);
        \Farn\EasyBackendStyle\InitialColors::prepare();
        $this->assertTrue(\Farn\EasyBackendStyle\InitialColors::enabled());
        $wpdb->query("DROP TABLE IF EXISTS {$legacy->tableName}");
    }
}
