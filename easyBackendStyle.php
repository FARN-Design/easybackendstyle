<?php

/*
Plugin Name:       Easy Backend-Style
Plugin URI:        https://github.com/farndesign/easyBackendStyle
Description:       Easily modify your backend interface's color schemes for a fresh look using an automated color generation feature based on two base colors, and have advanced customization options to fine-tune your backend's appearance.
Version:           2.1.4
Author:            FARN – digital brand design
Author URI:        https://www.farn.de
License:           GNU GENERAL PUBLIC LICENSE Version 3
Text Domain:       easybackendstyle
Domain Path:       /src/languages
@link              https://www.farn.de
@since             1.0.0
@package           EasyBackendStyle
*/

/*  Licence Placeholder
	See LICENCE.md file.
*/
//------------------------------------------Plugin Security----------------------------------------
use Farn\EasyBackendStyle\deprecated\easyBackendStyle_deprecated;
use Farn\EasyBackendStyle\ebs_DatabaseConnector;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

//------------------------------------------Plugin Code--------------------------------------------

include_once "vendor/autoload.php";


add_action( 'init', function (){
    load_plugin_textdomain( "easybackendstyle", false, 'easyBackendStyle/src/languages' );
} );

if ( version_compare( $GLOBALS['wp_version'], '7.0-alpha', '<' ) ) {
    \Farn\EasyBackendStyle\deprecated\easyBackendStyle_deprecated::Instance();
} else {
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

        $GLOBALS['ebsColorMapping'] = [

            "#f0f0f0" => "ebsBackground",
            "#0073aa" => "ebsLinks",
            "rgb(0, 149.5, 221)" => "ebsLinksHover",
            "var(--wp-admin-theme-color)" => "ebsPrimary",
            "var(--wp-admin-theme-color-darker-20)" => "ebsPrimaryDarker20",
            "#949494" => "ebsDisabledButtonText",
            "#cc1818" => "ebsDeleteLinks",
            "rgb(230.6842105263, 48.3157894737, 48.3157894737)" => "ebsDeleteLinksHover",
            "var(--wp-admin-theme-color--rgb" => "ebsPrimary",
            "#dddddd" => "ebsDisabledButtonBorder",
            "var(--wp-admin-theme-color-darker-10)" => "ebsPrimaryDarker10",
            "#fffffff" => "ebsPrimaryText",
            "#fff" => "ebsPrimaryText",
            "#52accc" => "ebsSecondary",
            "#096484" => "ebsTertiary",
            "#e1a948" => "ebsNotification",
            "#e5f8ff" => "ebsIcon",
            "#4796b3" => "ebsSubMenu",
            "#e2ecf1" => "ebsSubMenuText",
            "rgb(116.162375, 182.0949364754, 205.537625)" => "ebsSecondaryLighter",
            "rgb(109.571875, 185.228125, 212.128125)" => "ebsSecondaryLighter",
            "rgb(202.5, 152.1, 64.8)" => "ebsNotification",
            "rgb(7.3723404255, 81.914893617, 108.1276595745)" => "ebsTertiary",
            "rgb(232.1830985915, 189.5915492958, 115.8169014085)" => "ebsNotification",



            /*
            // Old variables with new color-mapping
            "#0073aa" => "ebsLinks",
            "#52accc" => "ebsBaseMenu",
            "#52accc" => "ebsMenuText",

            "#096484" => "ebsHighlight",
            "#096484" => "ebsHighlightText",

            "#e1a948" => "ebsNotification",

            "#f0f0f0" => "ebsBackground",
            "var(--wp-admin-theme-color)" => "ebsHighlight",
            "#4796b3" => "ebsSubMenuColor",
            "#949494" => "ebsDisabledButtonTextColor",
            "#dddddd" => "ebsDisabledButtonColor",
            "#e5f8ff" => "ebsIconColor",

            // New colors and new variables (for WordPress 7.0+)
            "#e2ecf1" => "ebsSubmenuText",
            "#cc1818" => "ebsDeleteLinks",
            "rgba(var(--wp-admin-theme-color--rgb), 0.08)" => "ebsHighlightHover",
            "rgba(var(--wp-admin-theme-color--rgb), 0.04)" => "ebsHighlightHover",
            "var(--wp-admin-theme-color-darker-10)" => "ebsHighlightHover",
            "var(--wp-admin-theme-color-darker-20)" => "ebsHighlightHover2",
            // "#1e1e1e" => "ebsCheckboxFocusBorder",
            */
            /* Missing old variables
             *
             * ebsFormInputsColor
             * ebsButtonHoverColor
             * ebsButtonTextColor
            */

        ];

        $GLOBALS['ebsPlugin'] = $this;
        register_activation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'activate' ) );
        register_deactivation_hook( __FILE__, array( $GLOBALS['ebsPlugin'], 'deactivate' ) );

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'linkToEBSSettingsPage'));

        add_action('admin_menu', array($this, 'sub_settings_page'));
        add_action('admin_head', array($this, 'ebs_backend_css'));
        add_action('wp_head', array($this, 'ebs_backend_css'));
        add_action('admin_enqueue_scripts', array($this, 'addScriptsAndStylesToMenuPages'));

        if (!class_exists('ebsDatabaseConnector')) {
            $this->dbc = new ebs_DatabaseConnector();
        }
        $this->dbc->checkFields();



        /*Function for adding a color scheme in the admin area
        wp_admin_css_color(
            'easybackendstyle',
            __( 'EasyBackendStyle', 'textdomain' ),
            admin_url( "css/colors/blue/colors.css" ),
            array('#fce4ec', '#f48fb1', '#e91e8c', '#c2185b'),
            array( 'base' => '#f3e5f5', 'focus' => '#ce93d8', 'current' => '#6a1b9a' )
        );*/

    }

    //On activation of the plugin
    function activate(): void
    {
        $this->dbc->setup_Database();
        $this->sub_settings_page();
        flush_rewrite_rules();
    }

    //On deactivation of the plugin
    function deactivate(): void
    {
        flush_rewrite_rules();
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
        include_once('src/ebs_SettingsSubMenu.php');
    }

    //In class function that calls the getValueFromDB() function from the DatabaseConnector.
    function getColor($name)
    {
        return $this->dbc->getValueFromDB($name)[0][0];
    }

    function linkToEBSSettingsPage($links)
    {

        //TODO Variablen Check
        if (get_user_locale() == "de_DE") {
            $links[] = '<a href="' . admin_url('options-general.php?page=easyBackendStyle') . '">' . esc_html('Einstellungen') . '</a>';
        } else {
            $links[] = '<a href="' . admin_url('options-general.php?page=easyBackendStyle') . '">' . esc_html('Settings') . '</a>';
        }

        return $links;
    }

    function ebs_backend_css(): void
    {
        /*
        if (get_user_option('admin_color') != 'fresh') {
            return;
        }
        */
        echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . '/resources/ebsMainCSS.css">';
        $cssRoot = "<style> :root {";

        foreach ($GLOBALS['ebsColorMapping'] as $oldColor => $newColor) {
            $cssRoot .= "--".$newColor.": ".$this->getColor($newColor).";";
        }

        $cssRoot .= "} </style>";
        echo $cssRoot;

        /* echo '
			<style>
                :root{
				    --ebsMenuText: ' . $this->getColor("menuText") . '; 
				    --ebsBaseMenu: ' . $this->getColor("baseMenu") . '; 
				    --ebsSubMenu: ' . $this->getColor("subMenu") . '; 
				    --ebsHighlight: ' . $this->getColor("highlight") . ';
					--ebsHighlightText: ' . $this->getColor("highlightText") . '; 
				    --ebsNotification: ' . $this->getColor("notification") . '; 
				    --ebsBackground: ' . $this->getColor("background") . '; 
				    --ebsLinks: ' . $this->getColor("links") . '; 
				    --ebsButtons: ' . $this->getColor("buttons") . '; 
				    --ebsDisabledButton: ' . $this->getColor("disabledButton") . ';
				    --ebsDisabledButtonText: ' . $this->getColor("disabledButtonText") . ';
				    --ebsIconColor: ' . $this->getColor("icon") . ';

				    
                }
            </style>'; */
    }

    function addScriptsAndStylesToMenuPages($hook)
    {
        $current_screen = get_current_screen();
        // scripts and styles for menu page
        if (strpos($current_screen->base, 'easyBackendStyle')) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('ebs_menuPageJS', plugins_url('resources/ebsMenuPage.js', __FILE__), array('wp-color-picker'), false, true);
            wp_enqueue_style('ebs_menuPageCSS', plugins_url('resources/ebsMenuPage.css', __FILE__));
        }
    }
}


