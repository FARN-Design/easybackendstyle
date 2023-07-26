<?php

if (!class_exists('ebsSettingsSubMenu')){
  $GLOBALS['ebsSettingsSubMenu'] = new ebs_SettingsSubMenu();
}

$GLOBALS['ebsSettingsSubMenu']->handleRequest();

/**
 * This class manages the content and Requests on the SubSetting page of the plugin.
 */
class ebs_SettingsSubMenu{

    //An object of the ebs_DatabaseConnector class
    private ebs_DatabaseConnector $dbc;
    //An object of the main ebs plugin class
    private easyBackendStyle $ebs;

    //Set a reference to the ebs plugin main php class and the ebs databaseConnector class.
    function __construct(){
        $this->ebs = $GLOBALS['ebsPlugin'];
        $this->dbc = $GLOBALS['ebsPlugin']->dbc;

        //Runs a check if all fields in the database are set correctly
        $this->dbc->checkFields();
    }

    /**
     * Handels all possible Request that can appear on the settings page. 
     * 
     * Writes an Error on the page if 
     *  1. The wrong scheme is loaded
     *  2. if the custom CSS field is not filled out correctly
     */
    public function handleRequest(): void {

        if (isset($_REQUEST['submit'])) {
            $this->dbc->saveValueInDB($_REQUEST['primaryColor'],'primaryColor');
            $this->dbc->saveValueInDB($_REQUEST['secondaryColor'],'secondaryColor');
            $this->dbc->saveValueInDB($_REQUEST['menuText'],'menuText');
            $this->dbc->saveValueInDB($_REQUEST['baseMenu'], 'baseMenu');
            $this->dbc->saveValueInDB($_REQUEST['subMenu'], 'subMenu');
            $this->dbc->saveValueInDB($_REQUEST['highlight'], 'highlight');
            $this->dbc->saveValueInDB($_REQUEST['highlightText'], 'highlightText');
            $this->dbc->saveValueInDB($_REQUEST['notification'], 'notification');
            $this->dbc->saveValueInDB($_REQUEST['notificationText'], 'notificationText');
            $this->dbc->saveValueInDB($_REQUEST['background'], 'background');
            $this->dbc->saveValueInDB($_REQUEST['links'], 'links');
            $this->dbc->saveValueInDB($_REQUEST['buttons'], 'buttons');
            $this->dbc->saveValueInDB($_REQUEST['buttonText'], 'buttonText');
            $this->dbc->saveValueInDB($_REQUEST['formInputs'], 'formInputs');
            $this->dbc->saveValueInDB($_REQUEST['linkHover'], 'linkHover');
            $this->dbc->saveValueInDB($_REQUEST['buttonHover'], 'buttonHover');
            $this->dbc->saveValueInDB($_REQUEST['disabledButton'], 'disabledButton');
            $this->dbc->saveValueInDB($_REQUEST['disabledButtonText'], 'disabledButtonText');
            $this->dbc->saveValueInDB($_REQUEST['icon'], 'icon');
        }

        if (isset($_REQUEST['resetDefaults'])){
            $this->dbc->resetDefaults();
        }
            
        if (get_user_option( 'admin_color' ) != 'fresh'){
            echo '<h5 style="color: #CC0000">'._e('Please select the default admin color scheme and reload the site to apply changes.', 'easybackendstyle'). '</h5>';
        }

        $this->ebs->ebs_backend_css();
    }
}

?>
<!-- HTML content for the settings page -->
<div class="wrap">

    <h1><?php _e('EasyBackendStyle settings', 'easybackendstyle')?></h1>

    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'resources/images/EasyBackendStyle-gross.svg' ); ?>" alt="Logo" width="200" height="200" align="right">

    <p class="description">
        <?php _e('Select two colors in the main color selectors. All other colors are selected automatically.', 'easybackendstyle')?>
    </p>

    <form action="" method="post" name="colorPickForm" id="colorPickForm">
        <h2><?php _e('main colors', 'easybackendstyle')?></h2>
        <div class="ebs_main_colors">
                <div class="wrapper_primaryColor">
                    <label for="primaryColor"><?php _e('primary color', 'easybackendstyle')?></label>
                    <input type="text" name="primaryColor" id="primaryColor" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("primaryColor")[0][0]);?>" class="ebs_mainColorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("primaryColor")[0][0]);?>" />
                </div>
                <div class="wrapper_secondaryColor">
                    <label for="secondaryColor"><?php _e('secondary color', 'easybackendstyle')?></label>
                    <input type="text" name="secondaryColor" id="secondaryColor" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("secondaryColor")[0][0]);?>" class="ebs_mainColorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("secondaryColor")[0][0]);?>" />
                </div>
        </div>
        <div class="ebs_advanced_settings_toggle">
            <h2><?php _e('advanced settings', 'easybackendstyle')?></h2>
        </div>
        <div class="ebs_advanced_settings">
            <div class="ebs_advanced_settings_columns">
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_menuText">
                        <label for="menuText"><?php _e('menu text', 'easybackendstyle')?></label>
                        <input type="text" name="menuText" id="menuText" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("menuText")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("menuText")[0][0]);?>" />
                    </div>          
                    <div class="wrapper_baseMenu">
                        <label for="baseMenu"><?php _e('base menu', 'easybackendstyle')?></label>
                        <input type="text" name="baseMenu" id="baseMenu" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("baseMenu")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("baseMenu")[0][0]);?>" />
                    </div>           
                    <div class="wrapper_subMenu">
                        <label for="subMenu"><?php _e('sub menu', 'easybackendstyle')?></label>
                        <input type="text" name="subMenu" id="subMenu" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("subMenu")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("subMenu")[0][0]);?>" />
                    </div>         
                    <div class="wrapper_highlight">
                        <label for="highlight"><?php _e('highlight', 'easybackendstyle')?></label>
                        <input type="text" name="highlight" id="highlight" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("highlight")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("highlight")[0][0]);?>" />
                    </div>
                    <div class="wrapper_highlightText">
                        <label for="highlightText"><?php _e('highlight text', 'easybackendstyle')?></label>
                        <input type="text" name="highlightText" id="highlightText" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("highlightText")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("highlightText")[0][0]);?>" />
                    </div>         
                    <div class="wrapper_notification">
                        <label for="notification"><?php _e('notification', 'easybackendstyle')?></label>
                        <input type="text" name="notification" id="notification" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("notification")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("notification")[0][0]);?>" />
                    </div>           
                    <div class="wrapper_notificationText">
                        <label for="notificationText"><?php _e('notification text', 'easybackendstyle')?></label>
                        <input type="text" name="notificationText" id="notificationText" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("notificationText")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("notificationText")[0][0]);?>" />
                    </div>
                    <div class="wrapper_icon">
                        <label for="icon"><?php _e('icon', 'easybackendstyle')?></label>
                        <input type="text" name="icon" id="icon" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("icon")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("icon")[0][0]);?>" />
                    </div>
                </div>
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_background">
                        <label for="background"><?php _e('background', 'easybackendstyle')?></label>
                        <input type="text" name="background" id="background" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("background")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("background")[0][0]);?>" />
                    </div>      
                    <div class="wrapper_links">
                        <label for="links"><?php _e('links', 'easybackendstyle')?></label>
                        <input type="text" name="links" id="links" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("links")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("links")[0][0]);?>" />
                    </div>
                    <div class="wrapper_linkHover">
                        <label for="linkHover"><?php _e('link hover', 'easybackendstyle')?></label>
                        <input type="text" name="linkHover" id="linkHover" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("linkHover")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("linkHover")[0][0]);?>" />
                    </div>      
                    <div class="wrapper_formInputs">
                        <label for="formInputs"><?php _e('form inputs', 'easybackendstyle')?></label>
                        <input type="text" name="formInputs" id="formInputs" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("formInputs")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("formInputs")[0][0]);?>" />
                    </div>   
                </div>
                <div class="ebs_advanced_settings_column">
                    <div class="wrapper_buttons">
                        <label for="buttons"><?php _e('button', 'easybackendstyle')?></label>
                        <input type="text" name="buttons" id="buttons" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttons")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttons")[0][0]);?>" />
                    </div>
                    <div class="wrapper_buttonHover">
                        <label for="buttonHover"><?php _e('button hover', 'easybackendstyle')?></label>
                        <input type="text" name="buttonHover" id="buttonHover" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttonHover")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttonHover")[0][0]);?>" />
                    </div>   
                    <div class="wrapper_buttonText">
                        <label for="buttonText"><?php _e('button text', 'easybackendstyle')?></label>
                        <input type="text" name="buttonText" id="buttonText" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttonText")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttonText")[0][0]);?>" />
                    </div> 
                    
                    <div class="wrapper_disabledButton">
                        <label for="disabledButton"><?php _e('disabled button', 'easybackendstyle')?></label>
                        <input type="text" name="disabledButton" id="disabledButton" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("disabledButton")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("disabledButton")[0][0]);?>" />
                    </div>          
                    <div class="wrapper_disabledButtonText">
                        <label for="disabledButtonText"><?php _e('disabled button color', 'easybackendstyle')?></label>
                        <input type="text" name="disabledButtonText" id="disabledButtonText" value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("disabledButtonText")[0][0]);?>" class="ebs_colorPicker" data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("disabledButtonText")[0][0]);?>" />
                    </div>
                </div>
            </div>
        </div>
        <br>
        <input type="submit" class="ebs_submitButton button button-primary" name="submit" id="submit" value="<?php _e('Save', 'easybackendstyle')?>">
        <input type="submit" class="ebs_submitButton button-link" name="resetDefaults" id="resetDefaults" value="<?php _e('Reset Defaults', 'easybackendstyle')?>">
    </form>

</div><!-- .wrap -->


