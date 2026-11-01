<?php
$tests_dir = getenv('WP_TESTS_DIR') ?: sys_get_temp_dir() . '/wordpress-tests-lib';
if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH') ?: dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills');
}
require $tests_dir . '/includes/functions.php';
tests_add_filter('muplugins_loaded', static function () {
    require dirname(__DIR__) . '/easyBackendStyle.php';
});
require $tests_dir . '/includes/bootstrap.php';
