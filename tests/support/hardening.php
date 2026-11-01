<?php
/** Run only against the disposable test database (Playground or WP PHPUnit). */
function ebs_assert($condition, $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function ebs_hardening_checks(): void {
    global $wpdb;
    $plugin = $GLOBALS['ebsPlugin'];
    $plugin->color_mapping();
    $db = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
    $old = new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector();
    ebs_assert($db->setup_Database(), 'Modern table setup');
    ebs_assert($old->setup_Database(), 'Legacy table setup');
    ebs_assert($old->saveColors(['primaryColor'=>'#33258a','menuText'=>'#123456','buttonText'=>'#abcdef']), 'Seed legacy colors');
    // Simulate an old active installation upgrading; the old table remains the backup.
    $wpdb->query("DROP TABLE {$db->tableName}");
    foreach (['ebs_schema_version','ebs_migration_pending','ebs_migration_lock','ebs_legacy_colors_backup'] as $key) delete_option($key);
    $filter = static function($sql) use ($db) {
        return str_starts_with($sql, "UPDATE {$db->tableName} SET Value") ? 'INVALID SQL EBS_TEST_WRITE_FAILURE' : $sql;
    };
    $previous = $wpdb->suppress_errors(true);
    add_filter('query', $filter);
    ebs_assert(!(new \Farn\EasyBackendStyle\ebs_MigrationHandler())->migration(), 'Failed migration must report failure');
    remove_filter('query', $filter);
    ebs_assert(get_option('ebs_schema_version') !== '2', 'Failed migration must not be marked complete');
    ebs_assert(get_option('ebs_migration_pending') === '1', 'Failed migration must remain retryable');
    $backup = get_option('ebs_legacy_colors_backup');
    ebs_assert($backup['primaryColor'] === '#33258a' && isset($backup['disabledButton']), 'Backup includes all original keys');
    ebs_assert((new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector())->tableExists(), 'Original table retained on failure');
    ebs_assert((new \Farn\EasyBackendStyle\ebs_MigrationHandler())->migration(), 'Retry migration');
    $db = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
    $colors = $db->allColors();
    ebs_assert($colors['ebsPrimary'] === '#33258a', 'Primary preserved');
    ebs_assert($colors['ebsPrimaryText'] === '#123456' && $colors['ebsSubMenuText'] === '#123456', 'Both text mappings preserved');
    ebs_assert($colors['ebsPrimaryButtonText'] === '#abcdef', 'Button text preserved');
    ebs_assert((new \Farn\EasyBackendStyle\deprecated\ebs_DatabaseConnector())->tableExists(), 'Original table retained after success');
    ebs_assert($db->saveColors(['ebsPrimary'=>'#aabbcc','ebsSecondary'=>'#112233']), 'Batch save');
    add_filter('query', $filter);
    ebs_assert(!$db->saveColors(['ebsPrimary'=>'#ffffff','ebsSecondary'=>'#ffffff']), 'Failed batch must return failure');
    remove_filter('query', $filter);
    $db = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
    ebs_assert($db->allColors()['ebsPrimary'] === '#aabbcc' && $db->allColors()['ebsSecondary'] === '#112233', 'Failed batch must preserve every previous value');
    ebs_assert(!(new \Farn\EasyBackendStyle\ebs_DatabaseConnector())->saveColors(['ebsPrimary'=>'</style>']), 'Reject CSS injection');
    // A missing old field uses its historical default without notices or a TypeError.
    $wpdb->query("DROP TABLE {$db->tableName}");
    $wpdb->delete($old->tableName, ['Variable'=>'menuText']);
    foreach (['ebs_schema_version','ebs_migration_pending','ebs_migration_lock','ebs_legacy_colors_backup'] as $key) delete_option($key);
    ebs_assert((new \Farn\EasyBackendStyle\ebs_MigrationHandler())->migration(), 'Missing old fields must not crash upgrade');
    $db = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
    ebs_assert($db->allColors()['ebsPrimaryText'] === $old->defaults()['menuText'], 'Historical default used');
    // Existing modern values take precedence, even when a retained legacy table exists.
    ebs_assert($db->saveColors(['ebsPrimary'=>'#654321']), 'Seed modern value');
    delete_option('ebs_schema_version');
    ebs_assert((new \Farn\EasyBackendStyle\ebs_MigrationHandler())->migration(), 'Adopt existing modern table');
    ebs_assert((new \Farn\EasyBackendStyle\ebs_DatabaseConnector())->allColors()['ebsPrimary'] === '#654321', 'Existing modern values not overwritten');
    // A denied CREATE must fail once, not recurse until the request runs out of memory.
    $broken = new \Farn\EasyBackendStyle\ebs_DatabaseConnector();
    $broken->tableName = $wpdb->prefix . 'ebs_test_denied_create';
    $createAttempts = 0;
    $deny = static function($sql) use ($broken, &$createAttempts) {
        if (str_starts_with($sql, "CREATE TABLE {$broken->tableName}")) {
            if (++$createAttempts > 2) throw new RuntimeException('Recursive table creation');
            return 'INVALID SQL EBS_TEST_CREATE_FAILURE';
        }
        return $sql;
    };
    add_filter('query', $deny);
    ebs_assert(!$broken->setup_Database(), 'Denied CREATE must return failure');
    remove_filter('query', $deny);
    ebs_assert($createAttempts === 1, 'Only one table creation attempt');
    $wpdb->suppress_errors($previous);
}
