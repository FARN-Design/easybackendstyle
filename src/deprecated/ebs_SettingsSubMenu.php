<?php

namespace Farn\EasyBackendStyle\deprecated;

$GLOBALS['ebsSettingsSubMenu'] = new ebs_SettingsSubMenu();

$GLOBALS['ebsSettingsSubMenu']->handleRequest();

/**
 * This class manages the content and Requests on the SubSetting page of the plugin.
 */
class ebs_SettingsSubMenu
{

    //An object of the ebs_DatabaseConnector class
    private ebs_DatabaseConnector $dbc;
    //An object of the main ebs plugin class
    private \Farn\EasyBackendStyle\deprecated\easyBackendStyle_deprecated $ebs;

    //Set a reference to the ebs plugin main php class and the ebs databaseConnector class.
    function __construct()
    {
        $this->ebs = $GLOBALS['ebsPlugin'];
        $this->dbc = $GLOBALS['ebsPlugin']->dbc;

        //Runs a check if all fields in the database are set correctly

    }

    /**
     * Handels all possible Request that can appear on the settings page.
     *
     * Writes an Error on the page if
     *  1. The wrong scheme is loaded
     *  2. if the custom CSS field is not filled out correctly
     */
    public function handleRequest(): void
    {

        if (!$this->dbc->checkFields()) { \Farn\EasyBackendStyle\AdminSettings::saveFailure(); return; }
        \Farn\EasyBackendStyle\AdminSettings::handle($this->dbc, array_fill_keys(['primaryColor', 'secondaryColor', 'menuText', 'baseMenu', 'subMenu', 'highlight', 'highlightText', 'notification', 'notificationText', 'background', 'links', 'buttons', 'buttonText', 'formInputs', 'linkHover', 'buttonHover', 'disabledButton', 'disabledButtonText', 'icon'], ''));

        if (get_user_option('admin_color') != 'fresh') {
            echo '<p class="notice notice-warning">' . esc_html__('Please select the default admin color scheme and reload the site to apply changes.', 'easybackendstyle') . '</p>';
        }

        $this->ebs->ebs_backend_css();
    }
}

?>
<!-- HTML content for the settings page -->
<div class="wrap ebs-admin">

    <?php \Farn\EasyBackendStyle\AdminSettings::header(); ?>

    <p class="description">
        <?php _e('Select two colors in the main color selectors. All other colors are selected automatically.', 'easybackendstyle') ?>
    </p>

    <form action="" method="post" name="colorPickForm" id="colorPickForm">
        <?php wp_nonce_field('ebs_save_colors', 'ebs_nonce'); ?>
        <h2><?php _e('Main colors fields', 'easybackendstyle') ?></h2>
        <div class="ebs_main_colors">
            <div class="wrapper_primaryColor">
                <label for="primaryColor"><?php _e('primary color', 'easybackendstyle') ?></label>
                <input type="text" name="primaryColor" id="primaryColor"
                       value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("primaryColor")); ?>"
                       class="ebs_mainColorPicker"
                       data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("primaryColor")); ?>"/>
            </div>
            <div class="wrapper_secondaryColor">
                <label for="secondaryColor"><?php _e('secondary color', 'easybackendstyle') ?></label>
                <input type="text" name="secondaryColor" id="secondaryColor"
                       value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("secondaryColor")); ?>"
                       class="ebs_mainColorPicker"
                       data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("secondaryColor")); ?>"/>
            </div>
        </div>
        <div class="ebs_advanced_settings_toggle">
            <h2><?php _e('All color fields', 'easybackendstyle') ?></h2>
        </div>
        <div class="ebs_advanced_settings">
            <div class="ebs_advanced_settings_columns">
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_menuText">
                        <label for="menuText"><?php _e('menu text', 'easybackendstyle') ?></label>
                        <input type="text" name="menuText" id="menuText"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("menuText")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("menuText")); ?>"/>
                    </div>
                    <div class="wrapper_baseMenu">
                        <label for="baseMenu"><?php _e('base menu', 'easybackendstyle') ?></label>
                        <input type="text" name="baseMenu" id="baseMenu"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("baseMenu")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("baseMenu")); ?>"/>
                    </div>
                    <div class="wrapper_subMenu">
                        <label for="subMenu"><?php _e('sub menu', 'easybackendstyle') ?></label>
                        <input type="text" name="subMenu" id="subMenu"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("subMenu")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("subMenu")); ?>"/>
                    </div>
                    <div class="wrapper_highlight">
                        <label for="highlight"><?php _e('highlight', 'easybackendstyle') ?></label>
                        <input type="text" name="highlight" id="highlight"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("highlight")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("highlight")); ?>"/>
                    </div>
                    <div class="wrapper_highlightText">
                        <label for="highlightText"><?php _e('highlight text', 'easybackendstyle') ?></label>
                        <input type="text" name="highlightText" id="highlightText"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("highlightText")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("highlightText")); ?>"/>
                    </div>
                    <div class="wrapper_notification">
                        <label for="notification"><?php _e('notification', 'easybackendstyle') ?></label>
                        <input type="text" name="notification" id="notification"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("notification")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("notification")); ?>"/>
                    </div>
                    <div class="wrapper_notificationText">
                        <label for="notificationText"><?php _e('notification text', 'easybackendstyle') ?></label>
                        <input type="text" name="notificationText" id="notificationText"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("notificationText")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("notificationText")); ?>"/>
                    </div>
                    <div class="wrapper_icon">
                        <label for="icon"><?php _e('icon', 'easybackendstyle') ?></label>
                        <input type="text" name="icon" id="icon"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("icon")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("icon")); ?>"/>
                    </div>
                </div>
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_background">
                        <label for="background"><?php _e('background', 'easybackendstyle') ?></label>
                        <input type="text" name="background" id="background"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("background")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("background")); ?>"/>
                    </div>
                    <div class="wrapper_links">
                        <label for="links"><?php _e('links', 'easybackendstyle') ?></label>
                        <input type="text" name="links" id="links"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("links")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("links")); ?>"/>
                    </div>
                    <div class="wrapper_linkHover">
                        <label for="linkHover"><?php _e('link hover', 'easybackendstyle') ?></label>
                        <input type="text" name="linkHover" id="linkHover"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("linkHover")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("linkHover")); ?>"/>
                    </div>
                    <div class="wrapper_formInputs">
                        <label for="formInputs"><?php _e('form inputs', 'easybackendstyle') ?></label>
                        <input type="text" name="formInputs" id="formInputs"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("formInputs")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("formInputs")); ?>"/>
                    </div>
                </div>
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_buttons">
                        <label for="buttons"><?php _e('button', 'easybackendstyle') ?></label>
                        <input type="text" name="buttons" id="buttons"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttons")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttons")); ?>"/>
                    </div>
                    <div class="wrapper_buttonHover">
                        <label for="buttonHover"><?php _e('button hover', 'easybackendstyle') ?></label>
                        <input type="text" name="buttonHover" id="buttonHover"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttonHover")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttonHover")); ?>"/>
                    </div>
                    <div class="wrapper_buttonText">
                        <label for="buttonText"><?php _e('button text', 'easybackendstyle') ?></label>
                        <input type="text" name="buttonText" id="buttonText"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttonText")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("buttonText")); ?>"/>
                    </div>

                    <div class="wrapper_disabledButton">
                        <label for="disabledButton"><?php _e('disabled button', 'easybackendstyle') ?></label>
                        <input type="text" name="disabledButton" id="disabledButton"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("disabledButton")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("disabledButton")); ?>"/>
                    </div>
                    <div class="wrapper_disabledButtonText">
                        <label for="disabledButtonText"><?php _e('disabled button color', 'easybackendstyle') ?></label>
                        <input type="text" name="disabledButtonText" id="disabledButtonText"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("disabledButtonText")); ?>"
                               class="ebs_colorPicker"
                               data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->getColor("disabledButtonText")); ?>"/>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <input type="submit" class="ebs_submitButton button button-primary" name="submit" id="submit"
               value="<?php _e('Save', 'easybackendstyle') ?>">
        <input type="submit" class="ebs_submitButton button-link" name="resetDefaults" id="resetDefaults"
               value="<?php _e('Reset Defaults', 'easybackendstyle') ?>">
    </form>

</div><!-- .wrap -->


