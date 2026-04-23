<?php

/*
Plugin Name: Easy Backend-Style
Plugin URI:        https://github.com/farndesign/easyBackendStyle
Description:       Easily modify your backend interface's color schemes for a fresh look using an automated color generation feature based on two base colors, and have advanced customization options to fine-tune your backend's appearance.
Version: 2.2.5
Author:            FARN – digital brand design
Author URI:        https://www.farn.de
Version: 2.2.5
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
    function __construct()
    {

    }
}


