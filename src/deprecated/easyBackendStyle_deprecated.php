<?php

namespace Farn\EasyBackendStyle\deprecated;

class easyBackendStyle_deprecated
{
    private static easyBackendStyle_deprecated $ebsPlugin;

    public static function Instance(): easyBackendStyle_deprecated
    {
        if(!isset(self::$ebsPlugin)){
            self::$ebsPlugin = new easyBackendStyle_deprecated();
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

        add_action('admin_menu', array($this, 'sub_settings_page'));
        add_action('admin_head', array($this, 'ebs_backend_css'));
        add_action('wp_head', array($this, 'ebs_backend_css'));
        add_action('admin_enqueue_scripts', array($this, 'addScriptsAndStylesToMenuPages'));

        if (!class_exists('ebsDatabaseConnector')) {
            $this->dbc = new ebs_DatabaseConnector();
        }
        $this->dbc->checkFields();
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
        include_once('ebs_SettingsSubMenu.php');
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

    /**
     * Main CSS injection of the selected colors from the database.
     */
    function ebs_backend_css(): void
    {
        if (get_user_option('admin_color') != 'fresh') {
            return;
        }
        echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . '/resources/ebsMainCSS.css">';
        echo '	
			<style>
                :root{
				    --menuTextColor: ' . $this->getColor("menuText") . '; 
				    --baseMenuColor: ' . $this->getColor("baseMenu") . '; 
				    --subMenuColor: ' . $this->getColor("subMenu") . '; 
				    --highlightColor: ' . $this->getColor("highlight") . ';
					--highlightTextColor: ' . $this->getColor("highlightText") . '; 
				    --notificationColor: ' . $this->getColor("notification") . '; 
				    --notificationTextColor: ' . $this->getColor("notificationText") . '; 
				    --backgroundColor: ' . $this->getColor("background") . '; 
				    --linksColor: ' . $this->getColor("links") . '; 
				    --buttonsColor: ' . $this->getColor("buttons") . '; 
				    --buttonTextColor: ' . $this->getColor("buttonText") . '; 
				    --formInputsColor: ' . $this->getColor("formInputs") . ';
				    --linkHoverColor: ' . $this->getColor("linkHover") . ';
				    --buttonHoverColor: ' . $this->getColor("buttonHover") . ';
				    --disabledButtonColor: ' . $this->getColor("disabledButton") . ';
				    --disabledButtonTextColor: ' . $this->getColor("disabledButtonText") . ';
				    --iconColor: ' . $this->getColor("icon") . ';
                }
            </style>';
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