<?php

/**
 * @link              https://farn.de
 * @since             0.0.1
 * @package           EasyBackendStyle
 * Plugin Name:       EasyBackendStyle
 * Plugin URI:        
 * Description:       Changing the background of the Backend in WordPress
 * Version:           0.0.1
 * Author:            Farn
 * Text Domain:       Marvin Taube - Farn
*/

// Default = #1d2327
$GLOBALS['backgroundMenuColor'] = '#1d2327';
// Default = #2271b1
$GLOBALS['selectedMenuColor'] = '#2271b1';
// Default = #1d2327
$GLOBALS['hoverMenuColor'] = '#1d2327';
// Icon and text primary color, Default = #f0f0f1
$GLOBALS['menuTextColor'] = '#f0f0f1';
// Icon and text secondary color. Used wheb hover. Default = #72aee6
$GLOBALS['menuTextHoverColor'] = '#72aee6';


//-----Database Setup------

require_once('ebs_DatabaseConnector.php');

function init_database(){
  
  setup_Database();
}
register_activation_hook( __FILE__, 'init_database' );


//-----Settings Sub Menu------


/**
 * Creates a new submenu page in the general settings.
 */
function test_settings_page(){
    add_submenu_page(
        'options-general.php', //name of the general settings file.
        'EasyBackendStyle',// page title
        'EasyBackendStyle',// menu title
        'manage_options',// capability
        'easyBackendStyle',// menu slug
        'display_settings_page' // callback function
    );
}
add_action('admin_menu', 'test_settings_page');

/**
 * Display the content on the Page
 */
function display_settings_page() {
    include_once plugin_dir_path( dirname( __FILE__ ) ) . 'easyBackendStyle/ebs_SettingsSubMenu.php';
}


//-----CSS Changes------

function my_custom_fonts() {
  echo '<style>
  #wpadminbar, 
  #wpadminbar .ab-empty-item, #wpadminbar a.ab-item, #wpadminbar>#wp-toolbar span.noticon,
  #wpadminbar .shortlink-input, #wpadminbar .menupop .ab-sub-wrapper,
  #wpadminbar .quicklinks .menupop ul.ab-sub-secondary, #wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu
  {
    background-color: '.getColorValueFromDB("backgroundMenuColor")[0][0].';
    color: '.getColorValueFromDB("menuTextColor")[0][0].' !important;
  }

  #wpadminbar>#wp-toolbar span.ab-label,
  #wpadminbar #adminbarsearch:before, #wpadminbar .ab-icon:before, #wpadminbar .ab-item:before 
  {
    color: '.getColorValueFromDB("menuTextColor")[0][0].';
  }

  #wpadminbar ul li:hover, #wpadminbar a.ab-item:hover,
  #wpadminbar .ab-top-menu>li.hover>.ab-item, #wpadminbar.nojq .quicklinks .ab-top-menu>li>.ab-item:focus, 
  #wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item, #wpadminbar:not(.mobile) .ab-top-menu>li>.ab-item:focus
  {
    background-color: '.getColorValueFromDB("hoverMenuColor")[0][0].';
    color: '.getColorValueFromDB("menuTextHoverColor")[0][0].'!important;
  }

  #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover>a, #wpadminbar .quicklinks .menupop ul li a:focus, 
  #wpadminbar .quicklinks .menupop ul li a:focus strong, #wpadminbar .quicklinks .menupop ul li a:hover, 
  #wpadminbar .quicklinks .menupop ul li a:hover strong, #wpadminbar .quicklinks .menupop.hover ul li a:focus, 
  #wpadminbar .quicklinks .menupop.hover ul li a:hover, #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:focus, 
  #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:hover, #wpadminbar li #adminbarsearch.adminbar-focused:before, 
  #wpadminbar li .ab-item:focus .ab-icon:before, #wpadminbar li .ab-item:focus:before, #wpadminbar li a:focus .ab-icon:before, 
  #wpadminbar li.hover .ab-icon:before, #wpadminbar li.hover .ab-item:before, #wpadminbar li:hover #adminbarsearch:before, 
  #wpadminbar li:hover .ab-icon:before, #wpadminbar li:hover .ab-item:before, #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus, 
  #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover

  #wpadminbar:not(.mobile)>#wp-toolbar a:focus span.ab-label, #wpadminbar:not(.mobile)>#wp-toolbar li:hover span.ab-label, 
  #wpadminbar>#wp-toolbar li.hover span.ab-label 
  {
    color: '.getColorValueFromDB("menuTextHoverColor")[0][0].'!important;
  }

  /* admin menu */
  #adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap
  { 
      background-color: '.getColorValueFromDB("backgroundMenuColor")[0][0].';
  }
  #adminmenu div.wp-menu-image:before, #adminmenu .wp-has-current-submenu div.wp-menu-image:before, #collapse-button, #collapse-button:focus, #adminmenu a,
  #adminmenu .wp-submenu li.current a, #adminmenu .wp-submenu a
  {
    color: '.getColorValueFromDB("menuTextColor")[0][0].';
  }

  /* selected admin menu */
  #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head, #adminmenu .wp-menu-arrow, 
  #adminmenu .wp-menu-arrow div, #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
  #adminmenu .current div.wp-menu-image:before, #adminmenu .wp-has-current-submenu div.wp-menu-image:before, 
  #adminmenu a.current:hover div.wp-menu-image:before, #adminmenu a.wp-has-current-submenu:hover div.wp-menu-image:before, 
  #adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image:before, #adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image:before, 
  #adminmenu li.wp-has-current-submenu:hover div.wp-menu-image:before
  {
    background-color: '.getColorValueFromDB("selectedMenuColor")[0][0].';
    color: '.getColorValueFromDB("menuTextColor")[0][0].';
  }

  /* Hover admin menu */
  #adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus
  {
    /* Color of the Field */
    background-color: '.getColorValueFromDB("hoverMenuColor")[0][0].';
  }

  /* Admin menu color of the image or text */
  #adminmenu a:hover, #adminmenu .wp-submenu li.current a:hover, #adminmenu .wp-submenu a:hover, #collapse-button:hover,
  #adminmenu li a:focus div.wp-menu-image:before, #adminmenu li.opensub div.wp-menu-image:before, #adminmenu li:hover div.wp-menu-image:before,
  #adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus
  {
    color: '.getColorValueFromDB("menuTextHoverColor")[0][0].';
  }
  </style>
  ';
}
add_action('admin_head', 'my_custom_fonts');
?>