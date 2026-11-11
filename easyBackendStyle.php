<?php

/*
Plugin Name:       Easy Backend-Style
Plugin URI:        https://www.farnlabs.com/easy-backend-style
Description:       Easily modify your backend interface's color schemes for a fresh look using an automated color generation feature based on your base colors, and have advanced customization options to fine-tune your backend's appearance.
Version: 3.2.5
Requires at least: 6.0
Requires PHP: 8.2
Author:            FARN Labs
Author URI:        https://www.farnlabs.com
Text Domain:       easybackendstyle
Domain Path:       /src/languages
License:           GPLv3
@link              https://www.farn.de
@since             1.0.0
@package           EasyBackendStyle
*/

//------------------------------------------Plugin Security----------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
//------------------------------------------Define constants & Use---------------------------------

define('EBS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

use Farn\EasyBackendStyle\deprecated\easyBackendStyle_deprecated;
use Farn\EasyBackendStyle\ebs_DatabaseConnector;
use Farn\EasyBackendStyle\ebs_MigrationHandler;

//------------------------------------------Plugin Code--------------------------------------------

include_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/src/AdminSettings.php";


add_action('init', static function () {
    if (is_admin()) \Farn\EasyBackendStyle\AdminSettings::loadTranslations();
}, 0);
add_action('change_locale', [\Farn\EasyBackendStyle\AdminSettings::class, 'loadTranslations'], 20);

if ( version_compare( $GLOBALS['wp_version'], '7.0-alpha', '<' ) ) {
    easyBackendStyle_deprecated::Instance();
} else {
    //New 7.X version
    easyBackendStyle::Instance();
}


//------------------------------------------Plugin Main Class--------------------------------------

/**
 * Main ebs plugin class. Manages the Plugin.
 */
class easyBackendStyle {

    private static easyBackendStyle $ebsPlugin;

    public static function Instance(): easyBackendStyle
    {
        if(!isset(self::$ebsPlugin)){
            self::$ebsPlugin = new easyBackendStyle();
        }
        return self::$ebsPlugin;
    }

    public ebs_DatabaseConnector $dbc;
    function __construct()
    {
        $GLOBALS['ebsPlugin'] = $this;

        register_activation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'activate' ) );
        register_deactivation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'deactivate' ) );

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'linkToEBSSettingsPage'));
        add_action('admin_init', [$this, 'initialize'], 0);
        add_action('admin_menu', array($this, 'sub_settings_page'));
        add_action('admin_head', array($this, 'ebs_root_variables_css'));
        add_action('admin_enqueue_scripts', array($this, 'addScriptsAndStylesToMenuPages'));
        add_action('wp_enqueue_scripts', array($this, 'addStylesToFrontendAdminbar'));

        $this->dbc = new ebs_DatabaseConnector();
    }

    /** Schema changes are confined to activation and authorized admin requests. */
    public function initialize(): void {
        $this->color_mapping();
        if (current_user_can('manage_options')) \Farn\EasyBackendStyle\InitialColors::prepare();
        if (current_user_can('manage_options') && !(new ebs_MigrationHandler())->migration()) {
            \Farn\EasyBackendStyle\AdminSettings::databaseWarning();
        }
        if (current_user_can('manage_options') && get_option(ebs_MigrationHandler::VERSION_OPTION) === ebs_MigrationHandler::VERSION) \Farn\EasyBackendStyle\InitialColors::initialize($this->dbc);
        $this->checkEbsColorSchemeOption();
        $this->registerColorScheme();
    }

    public function activate(): void {
        \Farn\EasyBackendStyle\InitialColors::prepare();
        $this->color_mapping();
        if (!(new ebs_MigrationHandler())->migration()) {
            \Farn\EasyBackendStyle\AdminSettings::databaseWarning();
            return;
        }
        \Farn\EasyBackendStyle\InitialColors::initialize($this->dbc);
        if (\Farn\EasyBackendStyle\InitialColors::enabled()) $this->changeAdminColorScheme(get_current_user_id());
    }

    public function deactivate(): void {
        // No rewrite rules or persistent color data belong to deactivation.
    }

    function color_mapping(): void{
        $GLOBALS['ebsColorMapping'] = [
            // key => [name, reserved legacy mapping slot, default value, description].
            // Build-time replacements live in build/admin-colors/source.json.
            "ebsBackground" => [__('background', 'easybackendstyle'),[],'#f0f0f1', __('Sets the background color of the page', 'easybackendstyle')],
            "ebsLinks" => [__('links', 'easybackendstyle'),[],'#2271b1', __('Sets the links color of the page', 'easybackendstyle')],
            "ebsLinksHover" => [__('link hover', 'easybackendstyle'),[],'#135e96', __('Sets the links hover color of the page', 'easybackendstyle')],
            "ebsPrimary" => [__('primary', 'easybackendstyle'),[],'#2271b1', __('Sets the main color for headings and buttons', 'easybackendstyle')],
            "ebsPrimaryDarker10" => [__('button hover', 'easybackendstyle'),[],'#135e96', __('Sets the button color when hovering over it', 'easybackendstyle')],
            "ebsPrimaryDarker20" => [__('button click', 'easybackendstyle'),[],'#0a4b78', __('Sets the button color when clicking it', 'easybackendstyle')],
            "ebsSecondaryLighter" => [__('nested submenu', 'easybackendstyle'),[],'#2c3338', __('Sets the adaptive accent color for image borders and nested menus', 'easybackendstyle')],
            "ebsDeleteLinks" => [__('delete links', 'easybackendstyle'),[],'#b32d2e', __('Sets the color of links that trigger a deletion', 'easybackendstyle')],
            "ebsDeleteLinksHover" => [__('delete links hover', 'easybackendstyle'),[],'#a00', __('Sets the color of delete links when hovering over them', 'easybackendstyle')],
            "ebsDisabledButtonText" => [__('disabled button text', 'easybackendstyle'),[],'#a7aaad', __('Sets the color of disabled button text', 'easybackendstyle')],
            "ebsSecondary" => [__('secondary', 'easybackendstyle'),[],'#1d2327', __('Sets the color of the menu', 'easybackendstyle')],
            "ebsTertiary" => [__('tertiary', 'easybackendstyle'),[],'#2271b1', __('Sets the color used to highlight the current menu item', 'easybackendstyle')],
            "ebsNotification" => [__('notification', 'easybackendstyle'),[],'#d63638', __('Sets the color of the notification', 'easybackendstyle')],
            "ebsIcon" => [__('icon', 'easybackendstyle'),[],'#a7aaad', __('Sets the color of the icons', 'easybackendstyle')],
            "ebsPrimaryText" => [__('primary text', 'easybackendstyle'),[],'#f0f0f1', __('Sets the color of all text', 'easybackendstyle')],
            "ebsSubMenu" => [__('submenu', 'easybackendstyle'),[],'#2c3338', __('Sets the color of the sub menu', 'easybackendstyle')],
            "ebsSubMenuText" => [__('submenu text', 'easybackendstyle'),[],'#c3c4c7', __('Sets the color of the sub menu text', 'easybackendstyle')],
            "ebsHighlightedText" => [__('highlight text', 'easybackendstyle'),[],'#ffffff', __('Sets the color of the highlighted text', 'easybackendstyle')],
            "ebsPrimaryButtonText" => [__('button text', 'easybackendstyle'),[],'#ffffff', __('Sets the color of the button text', 'easybackendstyle')],
        ];
    }


    /**
     * Creates a new submenu page in the general settings.
     */
    public function sub_settings_page(): void
    {
        add_action('load-settings_page_easyBackendStyle', [\Farn\EasyBackendStyle\AdminSettings::class, 'processPage']);
        add_submenu_page(
            'options-general.php', //name of the general settings file.
            'Easy Backend-Style',// page title
            'Easy Backend-Style',// menu title
            'manage_options',// capability
            'easyBackendStyle',// menu slug
            [$this, 'settings_page'] // callback function
        );
    }

    /**
     * Import of the content file for the setting page.
     */
    function settings_page(): void
    {
        include_once(__DIR__ . '/src/ebs_SettingsSubMenu.php');
    }

    public function getColor($name) {
        return $this->dbc->getValueFromDB($name)[0][0] ?? null;
    }

    function linkToEBSSettingsPage($links)
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=easyBackendStyle')) . '">' . esc_html__('Settings', 'easybackendstyle') . '</a>';
        return $links;
    }
    function ebs_root_variables_css(): void
    {
        if (!is_admin() || get_user_option('admin_color') !== 'personalizedcolorscheme') return;
        $this->renderVariables(':root');
    }

    private function renderVariables(string $selector): void {
        if (empty($GLOBALS['ebsColorMapping'])) $this->color_mapping();
        $colors = [];
        foreach ($GLOBALS['ebsColorMapping'] as $key => $definition) {
            $colors[$key] = sanitize_hex_color($this->getColor($key)) ?: $definition[2];
        }
        $cssRoot = "<style id='ebs-root-variables'> " . $selector . " {";

        foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue) {
            $cssRoot .= "--".$colorKey.": ".($colors[$colorKey]).";";
        }
        foreach (['ebsPrimary', 'ebsPrimaryDarker10', 'ebsPrimaryDarker20'] as $key) {
            $hex = ltrim($colors[$key], '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $rgb = array_map('hexdec', str_split($hex, 2));
            $cssRoot .= '--'.$key.'RGB: '.implode(', ', $rgb).';';
        }
        $cssRoot .= "} </style>";
        echo $cssRoot;
    }

    function addScriptsAndStylesToMenuPages($hook): void
    {
        // Load assets only on this plugin's Settings submenu.
        if ($hook === 'settings_page_easyBackendStyle') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('ebs_menuPageJS', plugins_url('resources/ebsMenuPage.js', __FILE__), array('wp-color-picker', 'wp-i18n'), filemtime(EBS_PLUGIN_PATH . '/resources/ebsMenuPage.js'), true);
            wp_set_script_translations('ebs_menuPageJS', 'easybackendstyle', EBS_PLUGIN_PATH . '/src/languages');
            wp_enqueue_style('ebs_adminLayout', plugins_url('resources/admin-layout.css', EBS_PLUGIN_PATH . '/easyBackendStyle.php'), [], filemtime(EBS_PLUGIN_PATH . '/resources/admin-layout.css'));
            wp_enqueue_style('ebs_menuPageCSS', plugins_url('resources/ebsMenuPage.css', __FILE__), ['ebs_adminLayout'], filemtime(EBS_PLUGIN_PATH . '/resources/ebsMenuPage.css'));
        }
    }

    function addStylesToFrontendAdminbar(): void {
        if (!is_user_logged_in() || !is_admin_bar_showing() || get_user_option('admin_color') !== 'personalizedcolorscheme') return;
        if (get_option(ebs_MigrationHandler::VERSION_OPTION) !== ebs_MigrationHandler::VERSION) return;
        wp_enqueue_style('ebsAdminbarFrontend', plugins_url('resources/ebsAdminbarFrontend.css', __FILE__), [], filemtime(EBS_PLUGIN_PATH . 'resources/ebsAdminbarFrontend.css'));
        // No document-wide variables or backend CSS on the public website.
        ob_start();
        $this->renderVariables('#wpadminbar');
        $css = ob_get_clean();
        wp_add_inline_style('ebsAdminbarFrontend', strip_tags($css));
    }

    function registerColorScheme(): void
    {
        if (!\Farn\EasyBackendStyle\InitialColors::enabled()) return;
        $colorsArray = [];
        $colorsArray[] = $this->getColor('ebsPrimary');
        $colorsArray[] = $this->getColor('ebsSecondary');
        $colorsArray[] = $this->getColor('ebsTertiary');

        wp_admin_css_color(
                'personalizedcolorscheme',
                __('Personalized Colors', 'easybackendstyle'),
                plugin_dir_url(__FILE__) . 'resources/ebsMainCSS.css' . '?' . filemtime( EBS_PLUGIN_PATH . '/resources/ebsMainCSS.css' ),
                $colorsArray
        );
    }

    function changeAdminColorScheme($user_id): void
    {
        if (!$user_id) return;
        update_user_option($user_id, 'admin_color', 'personalizedcolorscheme', true);
        if (get_user_option('admin_color', $user_id) === 'personalizedcolorscheme'){
            update_user_meta($user_id, 'ebs_scheme_initialized', true);
        }
    }
    function checkEbsColorSchemeOption(): void
    {
        if (!\Farn\EasyBackendStyle\InitialColors::enabled()) return;
        $user_id = get_current_user_id();
        if (get_user_meta($user_id, 'ebs_scheme_initialized', true) !== '1') {
            $this->changeAdminColorScheme($user_id);
        }
    }


}


