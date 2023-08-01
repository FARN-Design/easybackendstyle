=== EasyBackendStyle ===
Contributors: farndesign
Tags: tag1, tag2
Requires at least: 6.0
Tested up to: 6.2
Stable tag: 2.0.0
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

This plugin allows you to easily customize the colors in the backend. The changes are easily made via predefined fields, as well as custom CSS.

== Description ==

This Wordpress plugin allows you to easily customize the colors in the backend of wordpress. The changes are easily possible via predefined fields, as well as via custom CSS.

== Frequently Asked Questions ==

= How to install the plugin? =

1. Clone the repo or download the .zip archive
   ```sh
   git clone https://github.com/farndesign/easyBackendStyle.git
   ```
   or
   Download: https://github.com/farndesign/easyBackendStyle/archive/refs/heads/main.zip

2. Add the plugin files to the Wordpress instance.
  - Create a new Folder in: `/wp-content/plugins/easyBackendStyle`
  - Move all files into the new `easyBackendStyle` folder

3. Activate the plugin at the Wordpress plugin page http://localhost/wp-admin/plugins.php

#### Via Wordpress Plugin Store

1. Added later when plugin is released

= How to use the plugin? =

After activating, the settings page can be found as a subpage of the settings section.

At the settings page you can change the color of the different elements. The changes are applied after saving.

In addition, custom CSS can be added. This is always executed additionally and overwrites other content.

== Roadmap ==

- [ ] Think about more Features

See the [open issues](https://github.com/farndesign/easyBackendStyle/issues) for a full list of proposed features (and known issues).

== Changelog ==

= 2.0.0 =
* Feature: Changed all color pickers to the core Wordpress color picker
* Feature: New color fields: primaryColor, secondaryColor, highlightText and more
* Feature: Autofill-Function to fill all color fields based on primary and secondary color
* Feature: Live-Preview when making color changes
* Change: reset button is now a reset link
* Change: refactoring and extraction of main css changes
* Change: new logo and banner

= 1.1.0 =
* Feature: Icons now change color with the menuText color.
* Feature: You can now select a separate color for button Text.
* Bugfix: Database SQL request was not correct formatted.
* Bugfix: Background color changed in frontend when selected in backend
* Bugfix: Link color changed in the frontend when selected in the backend

= 1.0.4 =
* Stable build

= 1.0.3 =
* SVN Bugfix

= 1.0.2 =
* Bugfix Prefix error in ebs database fix

= 1.0.1 =
* Bugfix that occurred while loading the subMenuSettings page

= 1.0.0 =
* First release

== Upgrade Notice ==

= 1.0.0 =
* Download to use the first version of the plugin. Should download the update.
