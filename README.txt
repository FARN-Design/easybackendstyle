=== Easy Backend-Style ===
Contributors: farndesign
Tags: admin theme, backend theme, customize admin, Backend interface design, WordPress customization, Color scheme
Requires at least: 6.0
Tested up to: 6.3
Stable tag: 2.1.1
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

This plugin allows you to easily customize the colors in the backend. The changes are easily made via predefined fields.

== Description ==

Seamlessly adjust color schemes, effortlessly transforming the look and feel of your backend interface.
With an automated color generation feature from just two base colors, achieving a cohesive design is a breeze.
Dive deeper into customization with advanced fields, allowing precise adjustments to fine-tune your backend's appearance.
This plugin seamlessly integrates with all WordPress backend styles, ensuring a consistent experience across the board.

** Plugin Demo **

[Demo Video](https://www.youtube.com/watch?v=82_csWeNwFc)

[youtube https://www.youtube.com/watch?v=82_csWeNwFc]

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

#### Install via Wordpress Plugin Store

https://wordpress.com/de/plugins/easybackendstyle

= How to use the plugin? =

After activating, the settings page can be found as a subpage of the settings section.


Within the settings page, you have the ability to alter the primary pair of colors associated with your backend.
This action subsequently prompts an automatic adjustment of all linked advanced fields, guaranteeing a smooth harmonization with your chosen style.
In the event that a predilection for a more detailed design arises, you can manually select each distinct advanced field in the drop-down menu on the settings page.

== Screenshots ==

1. Advanced color settings
2. Color auto population


== Changelog ==

= 2.1.0 =
* Development: Updated the plugin description and tested for WordPress 6.3

= 2.0.2 =
* Bugfix: Added missing files to svn control

= 2.0.1 =
* Bugfix: Updated stable WordPress version

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
