<?php

namespace Farn\EasyBackendStyle\deprecated;

/**
 * @deprecated
 */
class easyBackendStyle_deprecated
{
    private static easyBackendStyle_deprecated $ebsPlugin;

    /**
     * @deprecated
     * @return easyBackendStyle_deprecated
     */
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
        register_activation_hook( EBS_PLUGIN_PATH . 'easyBackendStyle.php', array( $GLOBALS['ebsPlugin'], 'activate' ) );
        register_deactivation_hook( EBS_PLUGIN_PATH . 'easyBackendStyle.php', array( $GLOBALS['ebsPlugin'], 'deactivate' ) );

        add_filter('plugin_action_links_' . plugin_basename(EBS_PLUGIN_PATH . 'easyBackendStyle.php'), array($this, 'linkToEBSSettingsPage'));

        add_action('admin_menu', array($this, 'sub_settings_page'));
        add_action('admin_head', array($this, 'ebs_backend_css'));
        add_action('admin_init', [$this, 'initialize']);
        add_action('wp_enqueue_scripts', [$this, 'frontendAdminbar']);
        add_action('admin_enqueue_scripts', array($this, 'addScriptsAndStylesToMenuPages'));

        $this->dbc = new ebs_DatabaseConnector();
    }

    public function initialize(): void {
        if (current_user_can('manage_options')) {
            if (get_option('ebs_legacy_schema_version') !== '1') $this->activate();
            else \Farn\EasyBackendStyle\InitialColors::initialize($this->dbc);
        }
    }

    public function activate(): void {
        \Farn\EasyBackendStyle\InitialColors::prepare();
        if ($this->dbc->setup_Database()) {
            update_option('ebs_legacy_schema_version', '1', false);
            \Farn\EasyBackendStyle\InitialColors::initialize($this->dbc);
        }
        else \Farn\EasyBackendStyle\AdminSettings::databaseWarning();
    }

    public function deactivate(): void {}

    public function frontendAdminbar(): void {
        if (!\Farn\EasyBackendStyle\InitialColors::enabled()) return;
        if (!is_user_logged_in() || !is_admin_bar_showing() || get_user_option('admin_color') !== 'fresh') return;
        if (get_option('ebs_legacy_schema_version') !== '1') return;
        wp_enqueue_style('ebsAdminbarFrontend', plugins_url('resources/ebsAdminbarFrontend.css', EBS_PLUGIN_PATH . 'easyBackendStyle.php'), [], filemtime(EBS_PLUGIN_PATH . 'resources/ebsAdminbarFrontend.css'));
        $map = ['ebsSecondary' => 'baseMenu', 'ebsPrimaryText' => 'menuText', 'ebsIcon' => 'icon', 'ebsSubMenu' => 'subMenu'];
        $css = '#wpadminbar {';
        foreach ($map as $target => $key) $css .= '--' . $target . ':' . $this->getColor($key) . ';';
        wp_add_inline_style('ebsAdminbarFrontend', $css . '}');
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
        include_once(__DIR__ . '/ebs_SettingsSubMenu.php');
    }

    //In class function that calls the getValueFromDB() function from the DatabaseConnector.
    function getColor($name)
    {
        return sanitize_hex_color($this->dbc->getValueFromDB($name)[0][0] ?? '') ?: ($this->dbc->defaults()[$name] ?? '#000000');
    }

    function linkToEBSSettingsPage($links)
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=easyBackendStyle')) . '">' . esc_html__('Settings', 'easybackendstyle') . '</a>';
        return $links;
    }
    function ebs_backend_css(): void
    {
        if (!\Farn\EasyBackendStyle\InitialColors::enabled()) return;
        if (!is_admin() || get_user_option('admin_color') !== 'fresh') {
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
        // Load assets only on this plugin's Settings submenu.
        if ($hook === 'settings_page_easyBackendStyle') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('ebs_menuPageJS', plugins_url('resources/ebsMenuPage.js', __FILE__), array('wp-color-picker', 'wp-i18n'), filemtime(__DIR__ . '/resources/ebsMenuPage.js'), true);
            wp_set_script_translations('ebs_menuPageJS', 'easybackendstyle', EBS_PLUGIN_PATH . '/src/languages');
            wp_enqueue_style('ebs_adminLayout', plugins_url('resources/admin-layout.css', EBS_PLUGIN_PATH . '/easyBackendStyle.php'), [], filemtime(EBS_PLUGIN_PATH . '/resources/admin-layout.css'));
            wp_enqueue_style('ebs_menuPageCSS', plugins_url('resources/ebsMenuPage.css', __FILE__), ['ebs_adminLayout'], filemtime(__DIR__ . '/resources/ebsMenuPage.css'));
        }
    }
}