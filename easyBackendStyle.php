<?php

/*
Plugin Name:       Easy Backend-Style
Plugin URI:        https://github.com/farndesign/easyBackendStyle
Description:       Easily modify your backend interface's color schemes for a fresh look using an automated color generation feature based on two base colors, and have advanced customization options to fine-tune your backend's appearance.
Version: 3.2.0
Author:            FARN – digital brand design
Author URI:        https://www.farn.de
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
use Farn\EasyBackendStyle\pluginActivationHandler;
use Farn\EasyBackendStyle\Severity;
use Farn\EasyBackendStyle\Type;

//------------------------------------------Plugin Code--------------------------------------------

include_once __DIR__ . "/vendor/autoload.php";


add_action( 'init', function (){
    load_plugin_textdomain( "easybackendstyle", false, 'easyBackendStyle/src/languages' );
} );

if ( version_compare( $GLOBALS['wp_version'], '7.0-alpha', '<' ) ) {
    easyBackendStyle_deprecated::Instance();
} else {
    //New 7.X version
    new easyBackendStyle();
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
        $pluginActviationHandler = pluginActivationHandler::getInstance("ebs");
        $pluginActviationHandler->handleNotices();

        $GLOBALS['ebsPlugin'] = $this;

        register_activation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'activate' ) );
        register_deactivation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'deactivate' ) );

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'linkToEBSSettingsPage'));
        add_action('init', array($this, 'color_mapping'),7);
        add_action('init', array($this, 'is_migration_needed'), 8);
        add_action('init', array($this,'is_css_generated'), 9);
        add_action('init', array($this, 'init_db'));
        add_action('init', array($this,'checkEbsColorSchemeOption'), 10, 2);
        add_action('admin_init', array($this, 'registerColorScheme'),0);
        add_action('admin_menu', array($this, 'sub_settings_page'));
        add_action('admin_head', array($this, 'ebs_root_variables_css'));
        add_action('admin_enqueue_scripts', array($this, 'addScriptsAndStylesToMenuPages'));
        add_action('wp_enqueue_scripts', array($this, 'addStylesToFrontendAdminbar'));
        add_action('wp_head', array($this, 'ebs_root_variables_css'));

        if (!class_exists('ebsDatabaseConnector')) {
            $this->dbc = new ebs_DatabaseConnector();
        }
    }

    //On activation of the plugin
    function activate(): void
    {
        $this->color_mapping();
        $this->dbc->setup_Database();
        $this->sub_settings_page();
        flush_rewrite_rules();
        $this->generateColorsCss();
        $this->changeAdminColorScheme(get_current_user_id());
    }

    //On deactivation of the plugin
    function deactivate(): void
    {
        flush_rewrite_rules();
    }

    function color_mapping(): void{
        $GLOBALS['ebsColorMapping'] = [
            // key => [name, list of replaceable colors, default value, description]
            "ebsBackground" => [__('background', 'easybackendstyle'),["#f0f0f0"],'#f0f0f0', __('Sets the background color of the page', 'easybackendstyle')],
            "ebsLinks" => [__('links', 'easybackendstyle'),["#0073aa"],'#0073aa', __('Sets the links color of the page', 'easybackendstyle')],
            "ebsLinksHover" => [__('link hover', 'easybackendstyle'),["rgb(0, 149.5, 221)"],'#0095dd', __('Sets the links hover color of the page', 'easybackendstyle')],
            "ebsPrimary" => [__('primary', 'easybackendstyle'),["var(--wp-admin-theme-color)", "var(--wp-admin-theme-color--rgb)"],'#096484', __('Sets the main color for headings and buttons', 'easybackendstyle')],
            "ebsPrimaryDarker10" => [__('button hover', 'easybackendstyle'),["var(--wp-admin-theme-color-darker-10)"],'#07526c', __('Sets the button color when hovering over it', 'easybackendstyle')],
            "ebsPrimaryDarker20" => [__('button click', 'easybackendstyle'),["var(--wp-admin-theme-color-darker-20)"],'#064054', __('Sets the button color when clicking it', 'easybackendstyle')],
            "ebsSecondaryLighter" => [__('nested submenu', 'easybackendstyle'), ["rgb(116.162375, 182.0949364754, 205.537625)", "rgb(109.571875, 185.228125, 212.128125)"],'#74a6b9', __('Sets the adaptive accent color for image borders and nested menus', 'easybackendstyle')],
            "ebsDeleteLinks" => [__('delete links', 'easybackendstyle'), ["#cc1818"],'#cc1818', __('Sets the color of links that trigger a deletion', 'easybackendstyle')],
            "ebsDeleteLinksHover" => [__('delete links hover', 'easybackendstyle'), ["rgb(230.6842105263, 48.3157894737, 48.3157894737)"],'#e63004', __('Sets the color of delete links when hovering over them', 'easybackendstyle')],
            "ebsDisabledButtonText" => [__('disabled button text', 'easybackendstyle'), ["#949494"],'#949494', __('Sets the color of disabled button text', 'easybackendstyle')],
            "ebsSecondary" => [__('secondary', 'easybackendstyle'), ["#52accc"],'#52accc', __('Sets the color of the menu', 'easybackendstyle')],
            "ebsTertiary" => [__('tertiary', 'easybackendstyle'), ["#096484", "rgb(7.3723404255, 81.914893617, 108.1276595745)"],'#096484', __('Sets the color used to highlight the current menu item', 'easybackendstyle')],
            "ebsNotification" => [__('notification', 'easybackendstyle'), ["#e1a948","rgb(202.5, 152.1, 64.8)","rgb(232.1830985915, 189.5915492958, 115.8169014085)"],'#e1a948', __('Sets the color of the notification', 'easybackendstyle')],
            "ebsIcon" => [__('icon', 'easybackendstyle'), ["#e5f8ff"],'#e5f8ff', __('Sets the color of the icons', 'easybackendstyle')],
            "ebsPrimaryText" => [__('primary text', 'easybackendstyle'), ["#ffffff", "#fff"],'#ffffff', "Sets the color of all text"],
            "ebsSubMenu" => [__('submenu', 'easybackendstyle'), ["#4796b3"],'#4796b3', __('Sets the color of the sub menu', 'easybackendstyle')],
            "ebsSubMenuText" => [__('submenu text', 'easybackendstyle'), ["#e2ecf1"],'#e2ecf1', __('Sets the color of the sub menu text', 'easybackendstyle')],
            "ebsHighlightedText" => [__('highlight text', 'easybackendstyle'), ["#fff"],'#ffffff', __('Sets the color of the highlighted text', 'easybackendstyle')],
            "ebsPrimaryButtonText" => [__('button text', 'easybackendstyle'), ["#fff"],'#ffffff', __('Sets the color of the button text', 'easybackendstyle')],
        ];
    }
    function is_css_generated(): void {
        $bool = get_option('is_css_generated', false);
        if ($bool === false) {
            $this->generateColorsCss();
        }
    }
    function init_db():void
    {
        $this->dbc->setup_Database();
    }

    /**
     * Creates a new submenu page in the general settings.
     */
    public function sub_settings_page(): void
    {
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

    //In class function that calls the getValueFromDB() function from the DatabaseConnector.
    function getColor($name)
    {
        return $this->dbc->getValueFromDB($name)[0][0];
    }

    function linkToEBSSettingsPage($links)
    {
        if (get_user_locale() == "de_DE") {
            $links[] = '<a href="' . admin_url('options-general.php?page=easyBackendStyle') . '">' . esc_html('Einstellungen') . '</a>';
        } else {
            $links[] = '<a href="' . admin_url('options-general.php?page=easyBackendStyle') . '">' . esc_html('Settings') . '</a>';
        }

        return $links;
    }

    function ebs_root_variables_css(): void
    {
        $cssRoot = "<style id='ebs-root-variables'> :root {";

        foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue) {
            $cssRoot .= "--".$colorKey.": ".$this->getColor($colorKey).";";
        }
        $cssRoot .= "} </style>";
        echo $cssRoot;
    }

    function addScriptsAndStylesToMenuPages($hook): void
    {
        $current_screen = get_current_screen();
        // scripts and styles for menu page
        if (strpos($current_screen->base, 'easyBackendStyle')) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('ebs_menuPageJS', plugins_url('resources/ebsMenuPage.js', __FILE__), array('wp-color-picker'), false, true);
            wp_enqueue_style('ebs_menuPageCSS', plugins_url('resources/ebsMenuPage.css', __FILE__));
        }
    }

    function addStylesToFrontendAdminbar($hook): void
    {
        wp_enqueue_style('ebsAdminbarFrontend', plugins_url('resources/ebsAdminbarFrontend.css', __FILE__));
    }

    function generateColorsCss(): void
    {
        $baseColorFilePath = ABSPATH . 'wp-admin/css/colors/blue/colors.css';
        $baseColorFileContent = file_get_contents($baseColorFilePath);
        if(!$baseColorFileContent){
            $pluginActviationHandler = pluginActivationHandler::getInstance("ebs");
            $pluginActviationHandler->createNotice(
                Type::Error,
                "Could not activate plugin. Failed to load CSS.",
                Severity::Hard
            );
        }
        $newContent = $baseColorFileContent;

        // RegEx-String Replacement to insert variable for highlighted Text+Icons
        $newContent = preg_replace(
            [
                '/(#adminmenu li\.wp-has-current-submenu div\.wp-menu-image:before,\n#adminmenu a\.current:hover div\.wp-menu-image:before,\n#adminmenu li\.current div\.wp-menu-image:before,\n#adminmenu li\.wp-has-current-submenu a:focus div\.wp-menu-image:before,\n#adminmenu li\.wp-has-current-submenu\.opensub div\.wp-menu-image:before,\n#adminmenu li:hover div\.wp-menu-image:before,\n#adminmenu li a:focus div\.wp-menu-image:before,\n#adminmenu li\.opensub div\.wp-menu-image:before \{\n  color: )#fff(;)/',
                '/(#adminmenu \.menu-counter,\s*#adminmenu \.awaiting-mod,\s*#adminmenu \.update-plugins\s*\{\s*color:\s*)#fff(;)/',
                '/(#adminmenu li\.current a\.menu-top,\s*#adminmenu li\.wp-has-current-submenu a\.wp-has-current-submenu,\s*#adminmenu li\.wp-has-current-submenu \.wp-submenu \.wp-submenu-head,\s*.folded #adminmenu li\.current\.menu-top\s*\{\s*color:\s*)#fff(;)/',
                '/(#adminmenu a:hover,\n#adminmenu li\.menu-top:hover,\n#adminmenu li\.opensub > a\.menu-top,\n#adminmenu li > a\.menu-top:focus \{\n  color: )#fff(;)/',
                '/(#adminmenu li\.menu-top:hover div\.wp-menu-image:before,\n#adminmenu li\.opensub > a\.menu-top div\.wp-menu-image:before \{\n  color: )#fff(;)/',
                '/(\.wp-core-ui\s+\.button-primary\s*\{[^}]*?color:\s*)#fff(;)/',
            ],
            [
                '$1var(--ebsHighlightedText)$2',
                '$1var(--ebsHighlightedText)$2',
                '$1var(--ebsHighlightedText)$2',
                '$1var(--ebsHighlightedText)$2',
                '$1var(--ebsHighlightedText)$2',
                '$1var(--ebsPrimaryButtonText)$2',
            ], $newContent
        );
        foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue) {
            foreach ($colorValue[1] as $oldColor) {
                $newContent = str_replace($oldColor, "var(--".$colorKey.")", $newContent);
            }
        }

        file_put_contents(EBS_PLUGIN_PATH."/resources/ebsMainCSS.css", $newContent);
        add_option("is_css_generated", true);
    }
    function is_migration_needed(): void {
        $bool = get_option('is_migration_needed', false);
        if ($bool !== '1') {
            $this->initMigration();
        }
    }
    function initMigration(): void
    {
        $handler = new ebs_MigrationHandler();
        $handler->migration();
    }
    function registerColorScheme(): void
    {
        $colorsArray = [];
        $colorsArray[] = $this->getColor('ebsPrimary');
        $colorsArray[] = $this->getColor('ebsSecondary');
        $colorsArray[] = $this->getColor('ebsTertiary');

        wp_admin_css_color(
                'personalizedcolorscheme',
                __('Personalized Colors'),
                plugin_dir_url(__FILE__) . 'resources/ebsMainCSS.css',
                $colorsArray
        );
    }

    function changeAdminColorScheme($user_id): void
    {
        update_user_option($user_id, 'admin_color', 'personalizedcolorscheme', true);
        if (get_user_option('admin_color', $user_id) === 'personalizedcolorscheme'){
            update_user_meta($user_id, 'ebs_scheme_initialized', true);
        }
    }
    function checkEbsColorSchemeOption(): void
    {
        $user_id = get_current_user_id();
        if (get_user_meta($user_id, 'ebs_scheme_initialized', true) !== '1') {
            $this->changeAdminColorScheme($user_id);
        }
    }
}


