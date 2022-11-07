<?php

/**
 * Plugin Name: EasyBackendStyle
 * Plugin URI:        https://github.com/farndesign/easyBackendStyle
 * Description:       Changing the background of the Backend in WordPress
 * Version:           1.0.0
 * Author:            Farn - Digital Brand Design 
 * Author URI:        https://farn.de
 * License:           GNU GENERAL PUBLIC LICENSE Version 3
 * Text Domain:       ebs
 * Domain Path:       /languages
 * @link              https://farn.de
 * @since             1.0.0
 * @package           EasyBackendStyle
*/

/*  Licence Placeholder
	See LICENCE.md file.
*/
//------------------------------------------Plugin Security----------------------------------------

if (! defined('ABSPATH')){
  die;
}

//------------------------------------------Requirements-------------------------------------------

require_once('ebs_DatabaseConnector.php');

//------------------------------------------Plugin Code--------------------------------------------

if (!class_exists('ebsPlugin')){
  $GLOBALS['ebsPlugin'] = new easyBackendStyle();
}

//activation
register_activation_hook( __FILE__, array($GLOBALS['ebsPlugin'], 'activate'));

//deactivation
register_deactivation_hook(__FILE__, array($GLOBALS['ebsPlugin'], 'deactivate'));

function ebsTextDomainLoad(): void {
  load_plugin_textdomain("ebs", false, 'easyBackendStyle/languages');
}

add_action('init', 'ebsTextDomainLoad');

//------------------------------------------Plugin Main Class--------------------------------------

/**
 * Main ebs plugin class. Manages the Plugin.
 */
class easyBackendStyle
{
  public ebs_DatabaseConnector $dbc;

  function __construct(){

    add_action('admin_menu', array($this, 'sub_settings_page'));
    add_action('admin_head', array($this, 'ebs_backend_css'));
    add_action('wp_head', array($this, 'ebs_backend_css'));

    if (!class_exists('ebsDatabaseConnector')){
      $this->dbc = new ebs_DatabaseConnector();
    }
    $this->dbc->checkFields();
  }

  //On activation of the plugin
  function activate(): void {
    $this->dbc->setup_Database();
    $this->sub_settings_page();
    flush_rewrite_rules();
  }

  //On deactivation of the plugin
  function deactivate(): void {
    flush_rewrite_rules();
  }

  /**
   * Creates a new submenu page in the general settings.
  */
  public function sub_settings_page(): void {
    add_submenu_page(
        'options-general.php', //name of the general settings file.
        'EasyBackendStyle',// page title
        'EasyBackendStyle',// menu title
        'manage_options',// capability
        'easyBackendStyle',// menu slug
        [$this,'settings_page'] // callback function
      );
  }

  /**
   * Import of the content file for the setting page.
  */
  function settings_page(): void {
    include_once plugin_dir_path( dirname( __FILE__ ) ) . 'easyBackendStyle/ebs_SettingsSubMenu.php';
  }

  //In class function that calls the getValueFromDB() function from the DatabaseConnector.
  function getColor($name){
    return $this->dbc->getValueFromDB($name)[0][0];
  }

  /**
   * Main CSS injection of the selected colors from the database.
  */
  function ebs_backend_css(): void {
    if (get_user_option( 'admin_color' ) != 'fresh'){
      return;
    }
    echo '<style>
    :root{
      --wp-admin-theme-color: '.esc_attr(esc_attr($this->getColor("highlight"))).';
      --wp-admin-theme-color-darker-10: '.esc_attr(esc_attr($this->getColor("highlight"))).';
      --wp-admin-theme-color-darker-20: '.esc_attr(esc_attr($this->getColor("highlight"))).';
    }

    .wp-core-ui .button-link{
      color: '.esc_attr(esc_attr($this->getColor("links"))).'
    }

    .wrap .page-title-action, .components-button.is-primary{
      color: '.esc_attr(esc_attr($this->getColor("menuText"))).';
      background-color: '.esc_attr(esc_attr($this->getColor("buttons"))).';
    }

    .components-button.is-primary:disabled, .components-button.is-primary:disabled:active:enabled, 
    .components-button.is-primary[aria-disabled=true], .components-button.is-primary[aria-disabled=true]:active:enabled,
    .components-button.is-primary[aria-disabled=true]:enabled,
    .edit-post-header-toolbar.edit-post-header-toolbar__left>.edit-post-header-toolbar__inserter-toggle.has-icon{
      color: '.esc_attr(esc_attr($this->getColor("menuText"))).';
      background-color: '.esc_attr(esc_attr($this->getColor("buttons"))).';
    }

    </style>';


    //Template CSS
    echo '<style>
    body {
      background: '.esc_attr($this->getColor("background")).';
    }

    /* Links */
    a {
      color: '.esc_attr($this->getColor("links")).';
    }

    a:hover, a:active, a:focus {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("links")),1)) + 13061),-6).';
    }

    #post-body .misc-pub-post-status:before,
    #post-body #visibility:before,
    .curtime #timestamp:before,
    #post-body .misc-pub-revisions:before,
    span.wp-media-buttons-icon:before {
      color: currentColor;
    }

    /* Forms */
    input[type=checkbox]:checked::before {
      content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%23ffaf00%27%2F%3E%3C%2Fsvg%3E");
    }

    input[type=radio]:checked::before {
      background: '.esc_attr($this->getColor("formInputs")).';
    }

    .wp-core-ui input[type="reset"]:hover,
    .wp-core-ui input[type="reset"]:active {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("links")),1)) + 13061),-6).';
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="color"]:focus,
    input[type="date"]:focus,
    input[type="datetime"]:focus,
    input[type="datetime-local"]:focus,
    input[type="email"]:focus,
    input[type="month"]:focus,
    input[type="number"]:focus,
    input[type="search"]:focus,
    input[type="tel"]:focus,
    input[type="text"]:focus,
    input[type="time"]:focus,
    input[type="url"]:focus,
    input[type="week"]:focus,
    input[type="checkbox"]:focus,
    input[type="radio"]:focus,
    select:focus,
    textarea:focus {
      border-color: '.esc_attr($this->getColor("highlight")).';
      box-shadow: 0 0 0 1px '.esc_attr($this->getColor("highlight")).';
    }

    /* Core UI */
    .wp-core-ui .button,
    .wp-core-ui .button-secondary {
      color: '.esc_attr($this->getColor("buttons")).';
      border-color: '.esc_attr($this->getColor("buttons")).';
    }

    .wp-core-ui .button.hover,
    .wp-core-ui .button:hover,
    .wp-core-ui .button-secondary:hover,
    .wp-core-ui .button.focus,
    .wp-core-ui .button:focus,
    .wp-core-ui .button-secondary:focus {
      border-color: #;
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
    }

    .wp-core-ui .button.focus,
    .wp-core-ui .button:focus,
    .wp-core-ui .button-secondary:focus {
      border-color: '.esc_attr($this->getColor("buttons")).';
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      box-shadow: 0 0 0 1px '.esc_attr($this->getColor("buttons")).';
    }

    .wp-core-ui .button:active {
      background: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
    }

    .wp-core-ui .button.active,
    .wp-core-ui .button.active:focus,
    .wp-core-ui .button.active:hover {
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      box-shadow: inset 0 2px 5px -3px #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
    }

    .wp-core-ui .button-primary {
      background: '.esc_attr($this->getColor("buttons")).';
      border-color: '.esc_attr($this->getColor("buttons")).';
      color: '.esc_attr($this->getColor("menuText")).';
    }

    .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus {
      background: '.esc_attr($this->getColor("highlight")).'; 
      border-color: '.esc_attr($this->getColor("highlight")).';
      color: '.esc_attr($this->getColor("menuText")).';
    }

    .wp-core-ui .button-primary:focus {
      box-shadow: 0 0 0 1px '.esc_attr($this->getColor("menuText")).', 0 0 0 3px '.esc_attr($this->getColor("buttons")).';
    }

    .wp-core-ui .button-primary:active {
      background: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 6400),-6).';
      color: '.esc_attr($this->getColor("menuText")).';
    }

    .wp-core-ui .button-primary.active, .wp-core-ui .button-primary.active:focus, .wp-core-ui .button-primary.active:hover {
      background: '.esc_attr($this->getColor("buttons")).';
      color: '.esc_attr($this->getColor("menuText")).';
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 19457),-6).'; 
      box-shadow: inset 0 2px 5px -3px black;
    }

    .wp-core-ui .button-primary[disabled], .wp-core-ui .button-primary:disabled, .wp-core-ui .button-primary.button-primary-disabled, .wp-core-ui .button-primary.disabled {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 3681848),-6).' !important;
      background: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 10497),-6).' !important; 
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("buttons")),1)) - 10497),-6).' !important;
      text-shadow: none !important;
    }

    .wp-core-ui .button-group > .button.active {
      border-color: '.esc_attr($this->getColor("buttons")).';
    }

    .wp-core-ui .wp-ui-primary {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("baseMenu")).';
    }

    .wp-core-ui .wp-ui-text-primary {
      color: '.esc_attr($this->getColor("baseMenu")).';
    }

    .wp-core-ui .wp-ui-highlight {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("highlight")).';
    }

    .wp-core-ui .wp-ui-text-highlight {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    .wp-core-ui .wp-ui-notification {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("notification")).';
    }

    .wp-core-ui .wp-ui-text-notification {
      color: '.esc_attr($this->getColor("notification")).';
    }

    .wp-core-ui .wp-ui-text-icon {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    /* List tables */
    .wrap .add-new-h2:hover,
    .wrap .page-title-action:hover {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("baseMenu")).';
    }

    .view-switch a.current:before {
      color: '.esc_attr($this->getColor("baseMenu")).';
    }

    .view-switch a:hover:before {
      color: '.esc_attr($this->getColor("notification")).';
    }

    /* Admin Menu */
    #adminmenuback,
    #adminmenuwrap,
    #adminmenu {
      background: '.esc_attr($this->getColor("baseMenu")).';
    }

    #adminmenu a {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #adminmenu div.wp-menu-image:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    #adminmenu a:hover,
    #adminmenu li.menu-top:hover,
    #adminmenu li.opensub > a.menu-top,
    #adminmenu li > a.menu-top:focus {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("highlight")).';
    }

    #adminmenu li.menu-top:hover div.wp-menu-image:before,
    #adminmenu li.opensub > a.menu-top div.wp-menu-image:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Active tabs use a bottom border color that matches the page background color. */
    .about-wrap .nav-tab-active,
    .nav-tab-active,
    .nav-tab-active:hover {
      background-color: '.esc_attr($this->getColor("background")).';
      border-bottom-color: '.esc_attr($this->getColor("background")).';
    }

    /* Admin Menu: submenu */
    #adminmenu .wp-submenu,
    #adminmenu .wp-has-current-submenu .wp-submenu,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu {
      background: '.esc_attr($this->getColor("subMenu")).'; 
    }

    #adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after {
      border-right-color: '.esc_attr($this->getColor("subMenu")).';
    }

    #adminmenu .wp-submenu .wp-submenu-head {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #adminmenu .wp-submenu a,
    #adminmenu .wp-has-current-submenu .wp-submenu a,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #adminmenu .wp-submenu a:focus, #adminmenu .wp-submenu a:hover,
    #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
    #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a:focus,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a:hover,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    /* Admin Menu: current */
    #adminmenu .wp-submenu li.current a,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #adminmenu .wp-submenu li.current a:hover, #adminmenu .wp-submenu li.current a:focus,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a:hover,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a:focus,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:hover,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:focus {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    ul#adminmenu a.wp-has-current-submenu:after,
    ul#adminmenu > li.current > a.current:after {
      border-right-color: '.esc_attr($this->getColor("background")).';
    }

    #adminmenu li.current a.menu-top,
    #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
    #adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
    .folded #adminmenu li.current.menu-top {
      color: '.esc_attr($this->getColor("menuText")).';
      background: '.esc_attr($this->getColor("highlight")).';
    }

    #adminmenu li.wp-has-current-submenu div.wp-menu-image:before,
    #adminmenu a.current:hover div.wp-menu-image:before,
    #adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image:before,
    #adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image:before,
    #adminmenu li:hover div.wp-menu-image:before,
    #adminmenu li a:focus div.wp-menu-image:before,
    #adminmenu li.opensub div.wp-menu-image:before,
    .ie8 #adminmenu li.opensub div.wp-menu-image:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Admin Menu: bubble */
    #adminmenu .awaiting-mod,
    #adminmenu .update-plugins {
      color: '.esc_attr($this->getColor("menuText")).';
      background: '.esc_attr($this->getColor("notification")).';
    }

    #adminmenu li.current a .awaiting-mod,
    #adminmenu li a.wp-has-current-submenu .update-plugins,
    #adminmenu li:hover a .awaiting-mod,
    #adminmenu li.menu-top:hover > a .update-plugins {
      color: '.esc_attr($this->getColor("menuText")).';
      background: '.esc_attr($this->getColor("notification")).';
    }

    /* Admin Menu: collapse button */
    #collapse-button {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    #collapse-button:hover,
    #collapse-button:focus {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    /* Admin Bar */
    #wpadminbar {
      color: '.esc_attr($this->getColor("menuText")).';
      background: '.esc_attr($this->getColor("baseMenu"))
    .';
    }

    #wpadminbar .ab-item,
    #wpadminbar a.ab-item,
    #wpadminbar > #wp-toolbar span.ab-label,
    #wpadminbar > #wp-toolbar span.noticon {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #wpadminbar .ab-icon,
    #wpadminbar .ab-icon:before,
    #wpadminbar .ab-item:before,
    #wpadminbar .ab-item:after {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
    #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus,
    #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus,
    #wpadminbar.nojs .ab-top-menu > li.menupop:hover > .ab-item,
    #wpadminbar .ab-top-menu > li.menupop.hover > .ab-item {
      color: '.esc_attr($this->getColor("highlight")).';
      background: '.esc_attr($this->getColor("subMenu")).';
    }

    #wpadminbar:not(.mobile) > #wp-toolbar li:hover span.ab-label,
    #wpadminbar:not(.mobile) > #wp-toolbar li.hover span.ab-label,
    #wpadminbar:not(.mobile) > #wp-toolbar a:focus span.ab-label {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    #wpadminbar:not(.mobile) li:hover .ab-icon:before,
    #wpadminbar:not(.mobile) li:hover .ab-item:before,
    #wpadminbar:not(.mobile) li:hover .ab-item:after,
    #wpadminbar:not(.mobile) li:hover #adminbarsearch:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Admin Bar: submenu */
    #wpadminbar .menupop .ab-sub-wrapper {
      background: '.esc_attr($this->getColor("subMenu")).';
    }

    #wpadminbar .quicklinks .menupop ul.ab-sub-secondary,
    #wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu {
      background: '.esc_attr($this->getColor("subMenu")).';
    }

    #wpadminbar .ab-submenu .ab-item,
    #wpadminbar .quicklinks .menupop ul li a,
    #wpadminbar .quicklinks .menupop.hover ul li a,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #wpadminbar .quicklinks li .blavatar,
    #wpadminbar .menupop .menupop > .ab-item:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    #wpadminbar .quicklinks .menupop ul li a:hover,
    #wpadminbar .quicklinks .menupop ul li a:focus,
    #wpadminbar .quicklinks .menupop ul li a:hover strong,
    #wpadminbar .quicklinks .menupop ul li a:focus strong,
    #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a,
    #wpadminbar .quicklinks .menupop.hover ul li a:hover,
    #wpadminbar .quicklinks .menupop.hover ul li a:focus,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus,
    #wpadminbar li:hover .ab-icon:before,
    #wpadminbar li:hover .ab-item:before,
    #wpadminbar li a:focus .ab-icon:before,
    #wpadminbar li .ab-item:focus:before,
    #wpadminbar li .ab-item:focus .ab-icon:before,
    #wpadminbar li.hover .ab-icon:before,
    #wpadminbar li.hover .ab-item:before,
    #wpadminbar li:hover #adminbarsearch:before,
    #wpadminbar li #adminbarsearch.adminbar-focused:before {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    #wpadminbar .quicklinks li a:hover .blavatar,
    #wpadminbar .quicklinks li a:focus .blavatar,
    #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a .blavatar,
    #wpadminbar .menupop .menupop > .ab-item:hover:before,
    #wpadminbar.mobile .quicklinks .ab-icon:before,
    #wpadminbar.mobile .quicklinks .ab-item:before {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    #wpadminbar.mobile .quicklinks .hover .ab-icon:before,
    #wpadminbar.mobile .quicklinks .hover .ab-item:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    /* Admin Bar: search */
    #wpadminbar #adminbarsearch:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    #wpadminbar > #wp-toolbar > #wp-admin-bar-top-secondary > #wp-admin-bar-search #adminbarsearch input.adminbar-input:focus {
      color: '.esc_attr($this->getColor("menuText")).';
      background: #'.substr("000000".dechex(2458366 - hexdec(substr(esc_attr($this->getColor("baseMenu")),1))),-6).';
    }

    /* Admin Bar: recovery mode */
    #wpadminbar #wp-admin-bar-recovery-mode {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("notification")).';
    }

    #wpadminbar #wp-admin-bar-recovery-mode .ab-item,
    #wpadminbar #wp-admin-bar-recovery-mode a.ab-item {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #wpadminbar .ab-top-menu > #wp-admin-bar-recovery-mode.hover > .ab-item,
    #wpadminbar.nojq .quicklinks .ab-top-menu > #wp-admin-bar-recovery-mode > .ab-item:focus,
    #wpadminbar:not(.mobile) .ab-top-menu > #wp-admin-bar-recovery-mode:hover > .ab-item,
    #wpadminbar:not(.mobile) .ab-top-menu > #wp-admin-bar-recovery-mode > .ab-item:focus {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("notification")),1)) - 1638400),-6).';
    }

    /* Admin Bar: my account */
    #wpadminbar .quicklinks li#wp-admin-bar-my-account.with-avatar > a img {
      border-color: #'.substr("000000".dechex(2458366 - hexdec(substr(esc_attr($this->getColor("baseMenu")),1))),-6).';
      background-color: #'.substr("000000".dechex(2458366 - hexdec(substr(esc_attr($this->getColor("baseMenu")),1))),-6).';
    }

    #wpadminbar #wp-admin-bar-user-info .display-name {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    #wpadminbar #wp-admin-bar-user-info a:hover .display-name {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    #wpadminbar #wp-admin-bar-user-info .username {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Pointers */
    .wp-pointer .wp-pointer-content h3 {
      background-color: '.esc_attr($this->getColor("highlight")).';
      border-color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("highlight")),1)) - 327705),-6).'; 
    }

    .wp-pointer .wp-pointer-content h3:before {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    .wp-pointer.wp-pointer-top .wp-pointer-arrow,
    .wp-pointer.wp-pointer-top .wp-pointer-arrow-inner,
    .wp-pointer.wp-pointer-undefined .wp-pointer-arrow,
    .wp-pointer.wp-pointer-undefined .wp-pointer-arrow-inner {
      border-bottom-color: '.esc_attr($this->getColor("highlight")).';
    }

    /* Media */
    .media-item .bar,
    .media-progress-bar div {
      background-color: '.esc_attr($this->getColor("highlight")).';
    }

    .details.attachment {
      box-shadow: inset 0 0 0 3px '.esc_attr($this->getColor("menuText")).', inset 0 0 0 7px '.esc_attr($this->getColor("highlight")).';
    }

    .attachment.details .check {
      background-color: '.esc_attr($this->getColor("highlight")).';
      box-shadow: 0 0 0 1px '.esc_attr($this->getColor("menuText")).', 0 0 0 2px '.esc_attr($this->getColor("highlight")).';
    }

    .media-selection .attachment.selection.details .thumbnail {
      box-shadow: 0 0 0 1px '.esc_attr($this->getColor("menuText")).', 0 0 0 3px '.esc_attr($this->getColor("highlight")).';
    }

    /* Themes */
    .theme-browser .theme.active .theme-name,
    .theme-browser .theme.add-new-theme a:hover:after,
    .theme-browser .theme.add-new-theme a:focus:after {
      background: '.esc_attr($this->getColor("highlight")).';
    }

    .theme-browser .theme.add-new-theme a:hover span:after,
    .theme-browser .theme.add-new-theme a:focus span:after {
      color: '.esc_attr($this->getColor("highlight")).';
    }

    .theme-section.current,
    .theme-filter.current {
      border-bottom-color: '.esc_attr($this->getColor("baseMenu"))
    .';
    }

    body.more-filters-opened .more-filters {
      color: '.esc_attr($this->getColor("menuText")).';
      background-color: '.esc_attr($this->getColor("baseMenu"))
    .';
    }

    body.more-filters-opened .more-filters:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    body.more-filters-opened .more-filters:hover,
    body.more-filters-opened .more-filters:focus {
      background-color: '.esc_attr($this->getColor("highlight")).';
      color: '.esc_attr($this->getColor("menuText")).';
    }

    body.more-filters-opened .more-filters:hover:before,
    body.more-filters-opened .more-filters:focus:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Widgets */
    .widgets-chooser li.widgets-chooser-selected {
      background-color: '.esc_attr($this->getColor("highlight")).';
      color: '.esc_attr($this->getColor("menuText")).';
    }

    .widgets-chooser li.widgets-chooser-selected:before,
    .widgets-chooser li.widgets-chooser-selected:focus:before {
      color: '.esc_attr($this->getColor("menuText")).';
    }

    /* Responsive Component */
    div#wp-responsive-toggle a:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    .wp-responsive-open div#wp-responsive-toggle a {
      border-color: transparent;
      background: '.esc_attr($this->getColor("highlight")).';
    }

    .wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle a {
      background: '.esc_attr($this->getColor("subMenu")).';
    }

    .wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle .ab-icon:before {
      color: #'.substr("000000".dechex(hexdec(substr(esc_attr($this->getColor("menuText")),1)) - 920588),-6).';
    }

    /* TinyMCE */
    .mce-container.mce-menu .mce-menu-item:hover,
    .mce-container.mce-menu .mce-menu-item.mce-selected,
    .mce-container.mce-menu .mce-menu-item:focus,
    .mce-container.mce-menu .mce-menu-item-normal.mce-active,
    .mce-container.mce-menu .mce-menu-item-preview.mce-active {
      background: '.esc_attr(esc_attr($this->getColor("highlight"))).';
    }</style>';
  }
}

