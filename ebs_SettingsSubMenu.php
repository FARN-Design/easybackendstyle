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

            $this->dbc->saveValueInDB($_REQUEST['menuText'],'menuText');
            $this->dbc->saveValueInDB($_REQUEST['baseMenu'], 'baseMenu');
            $this->dbc->saveValueInDB($_REQUEST['subMenu'], 'subMenu');
            $this->dbc->saveValueInDB($_REQUEST['highlight'], 'highlight');
            $this->dbc->saveValueInDB($_REQUEST['notification'], 'notification');
            $this->dbc->saveValueInDB($_REQUEST['background'], 'background');
            $this->dbc->saveValueInDB($_REQUEST['links'], 'links');
            $this->dbc->saveValueInDB($_REQUEST['buttons'], 'buttons');
            $this->dbc->saveValueInDB($_REQUEST['buttonText'], 'buttonText');
            $this->dbc->saveValueInDB($_REQUEST['formInputs'], 'formInputs');
        }

        if (isset($_REQUEST['resetDefaults'])){
            $this->dbc->resetDefaults();
        }
            
        if (get_user_option( 'admin_color' ) != 'fresh'){
            echo '<h5 style="color: #CC0000">'._e('Please select the default admin color scheme and reload the site to apply changes.', 'ebs'). '</h5>';
        }

        $this->ebs->ebs_backend_css();
    }
}

?>
<!-- HTML content for the settings page -->
<div class="wrap">

    <h1><?php _e('Settings for EasyBackendStyle Plugin ', 'ebs')?></h1>

    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'resources/images/EasyBackendStyle-Logo 500x500.jpg' ); ?>" alt="Logo" width="200" height="200" align="right">

    <p class="description">
        <?php _e('This is the settings Page for the EasyBackendStyle Plugin.', 'ebs')?>
    </p>

    <form action="" method="post" name="colorPickForm" id="colorPickForm">
        <table>
            <tbody>
                <tr>
                    <th scope="row"><label for="menuText"><?php _e('Menu Text', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="menuText" id="menuText" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("menuText")[0][0]);?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="baseMenu"><?php _e('Base Menu', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="baseMenu" id="baseMenu" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("baseMenu")[0][0]); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="subMenu"><?php _e('Sub Menu', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="subMenu" id="subMenu" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("subMenu")[0][0]);?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="highlight"><?php _e('Highlight', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="highlight" id="highlight" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("highlight")[0][0]);?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="notification"><?php _e('Notification', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="notification" id="notification" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("notification")[0][0]);?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="background"><?php _e('Background', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="background" id="background" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("background")[0][0]);?>">
                    </td>
                </tr>
               
                <tr>
                    <th scope="row"><label for="links"><?php _e('Links', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="links" id="links" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("links")[0][0]);?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="buttons"><?php _e('Buttons', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="buttons" id="buttons" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttons")[0][0]);?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="buttonText"><?php _e('ButtonText', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="buttonText" id="buttonText" class="small-text"
                               value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("buttonText")[0][0]);?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="formInputs"><?php _e('Form Inputs', 'ebs')?></label></th>
                    <td>
                        <input type="color" name="formInputs" id="formInputs" class="small-text"
                        value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB("formInputs")[0][0]);?>">
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="submit" class="button button-primary" name="submit" id="submit" value="<?php _e('Save', 'ebs')?>">
        <br>
        <input type="submit" class="button-link" name="resetDefaults" id="resetDefaults" value="<?php _e('Reset Defaults', 'ebs')?>">
    </form>

</div><!-- .wrap -->
<script src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'resources/ebsScript.js');?>"></script>

