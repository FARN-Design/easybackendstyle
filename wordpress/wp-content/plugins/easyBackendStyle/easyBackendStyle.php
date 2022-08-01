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
 
/**
 * Display the content on the Page
 */
function display_hello_world_page() {
    echo "<h1>Hello World & Farn!</h1>";
}

/**
 * Creates a new submenu page in the general settings.
 */
function test_settings_page()
{
    add_submenu_page(
        'options-general.php', //name of the general settings file.
        'Hello World',// page title
        'Hello World',// menu title
        'manage_options',// capability
        'hello-world',// menu slug
        'display_hello_world_page' // callback function
    );
}
//Execute the method in the admin_menu loop
add_action('admin_menu', 'test_settings_page');


add_action('admin_head', 'my_custom_fonts');

function my_custom_fonts() {
  echo '<style>
  #wpadminbar 
  { 
      background-color: #0000ff; 
  } 
  #adminmenuback, #adminmenu 
  { 
      background-color: #0000ff; 
  }     
  </style>
  ';
}
?>