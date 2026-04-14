<?php


namespace Farn\EasyBackendStyle;
if (!class_exists('ebsSettingsSubMenu')) {
    $GLOBALS['ebsSettingsSubMenu'] = new ebs_SettingsSubMenu();
}

$GLOBALS['ebsSettingsSubMenu']->handleRequest();

/**
 * This class manages the content and Requests on the SubSetting page of the plugin.
 */
class ebs_SettingsSubMenu
{

    //An object of the ebs_DatabaseConnector class
    private \Farn\EasyBackendStyle\ebs_DatabaseConnector $dbc;
    //An object of the main ebs plugin class
    private \easyBackendStyle $ebs;

    //Set a reference to the ebs plugin main php class and the ebs databaseConnector class.
    function __construct()
    {
        $this->ebs = $GLOBALS['ebsPlugin'];
        $this->dbc = $GLOBALS['ebsPlugin']->dbc;

        //Runs a check if all fields in the database are set correctly
        $this->dbc->checkFields();
    }

    function generateColorsCss(){
        $baseColorFilePath = ABSPATH . 'wp-admin/css/colors/blue/colors.css';
        $baseColorFileContent = file_get_contents($baseColorFilePath);
        $newContent = $baseColorFileContent;

        print_r($GLOBALS['ebsColorMapping']);
        foreach ($GLOBALS['ebsColorMapping'] as $oldColor => $newColor) {
            $newContent = str_replace($oldColor, "var(--".$newColor.")", $newContent);

        }
        // TODO: exchange hardcoded Plugin name
        file_put_contents(WP_PLUGIN_DIR."/easybackendstyle/resources/ebsMainCSS.css", $newContent);
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

        if (isset($_REQUEST['submit'])) {
            // TODO: Transform PrimaryColor hex to rgb
            $this->dbc->saveValueInDB($_REQUEST['primaryColor'], 'primaryColor');
            $this->dbc->saveValueInDB($_REQUEST['secondaryColor'], 'secondaryColor');
            $this->dbc->saveValueInDB($_REQUEST['menuText'], 'menuText');
            $this->dbc->saveValueInDB($_REQUEST['baseMenu'], 'baseMenu');
            $this->dbc->saveValueInDB($_REQUEST['subMenu'], 'subMenu');
            $this->dbc->saveValueInDB($_REQUEST['highlight'], 'highlight');
            $this->dbc->saveValueInDB($_REQUEST['highlightText'], 'highlightText');
            $this->dbc->saveValueInDB($_REQUEST['notification'], 'notification');
            $this->dbc->saveValueInDB($_REQUEST['background'], 'background');
            $this->dbc->saveValueInDB($_REQUEST['links'], 'links');
            $this->dbc->saveValueInDB($_REQUEST['disabledButton'], 'disabledButton');
            $this->dbc->saveValueInDB($_REQUEST['disabledButtonText'], 'disabledButtonText');
            $this->dbc->saveValueInDB($_REQUEST['icon'], 'icon');
            $this->dbc->saveValueInDB($_REQUEST['subMenuText'], 'subMenuText');
            $this->dbc->saveValueInDB($_REQUEST['deleteLinks'], 'deleteLinks');
            $this->dbc->saveValueInDB($_REQUEST['highlightHover'], 'highlightHover');
            $this->dbc->saveValueInDB($_REQUEST['highlightHover2'], 'highlightHover2');


            $this->generateColorsCss();
        }

        if (isset($_REQUEST['resetDefaults'])) {
            $this->dbc->resetDefaults();
        }

        if (get_user_option('admin_color') != 'fresh') {
            echo '<h5 style="color: #CC0000">' . _e('Please select the default admin color scheme and reload the site to apply changes.', 'easybackendstyle') . '</h5>';
        }

        $this->ebs->ebs_backend_css();
    }
}

?>
<!-- HTML content for the settings page -->
<div class="wrap">

    <h1><?php _e('Easy Backend-Style settings', 'easybackendstyle') ?></h1>

    <p class="description">
        <?php _e('Select two colors in the main color selectors. All other colors are selected automatically.', 'easybackendstyle') ?>
    </p>

    <form action="" method="post" name="colorPickForm" id="colorPickForm">
        <h2><?php _e('Main colors fields', 'easybackendstyle') ?></h2>
        <div class="ebs_main_colors">

            <?php $mainColors = [];

            foreach ($GLOBALS['ebsColorMapping'] as $oldColor => $newColor){
                    if($newColor != "ebsPrimary" && $newColor != "ebsSecondary" && $newColor != "ebsTertiary"){
                        continue;
                    }
                    if(in_array($newColor, $mainColors)){
                        continue;
                    }
                    $mainColors[] = $newColor;?>
                <div class="wrapper_<?php echo $newColor; ?>">
                    <label for="<?php echo $newColor; ?>"> <?php echo $newColor; ?> </label>
                    <input type="text" name="<?php echo $newColor; ?>" id="<?php echo $newColor; ?>"
                           value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($newColor)[0][0]); ?>"
                           class="ebs_mainColorPicker"
                           data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($newColor)[0][0]); ?>"/>
                </div>
            <?php } ?>


        </div>
        <div class="ebs_advanced_settings_toggle">
            <h2><?php _e('All color fields', 'easybackendstyle') ?></h2>
        </div>
        <div class="ebs_advanced_settings">
            <div class="ebs_advanced_settings_columns">
                <div class="ebs_advanced_settings_column">

                    <?php $advancedColorFields = [];

                    foreach ($GLOBALS['ebsColorMapping'] as $oldColor => $newColor){
                        if($newColor == "ebsPrimary" || $newColor == "ebsSecondary" || $newColor == "ebsTertiary"){
                            continue;
                        }
                        if(in_array($newColor, $advancedColorFields)){
                            continue;
                        }
                        $advancedColorFields[] = $newColor;?>
                        <div class="wrapper_<?php echo $newColor; ?>">
                            <label for="<?php echo $newColor; ?>"> <?php echo $newColor; ?> </label>
                            <input type="text" name="<?php echo $newColor; ?>" id="<?php echo $newColor; ?>"
                                   value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($newColor)[0][0]); ?>"
                                   class="ebs_mainColorPicker"
                                   data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($newColor)[0][0]); ?>"/>
                        </div>
                    <?php } ?>
                </div>
                <div class="ebs_advanced_settings_column">
                </div>
        </div>
        <br>
        <input type="submit" class="ebs_submitButton button button-primary" name="submit" id="submit"
               value="<?php _e('Save', 'easybackendstyle') ?>">
        <input type="submit" class="ebs_submitButton button-link" name="resetDefaults" id="resetDefaults"
               value="<?php _e('Reset Defaults', 'easybackendstyle') ?>">
    </form>

</div><!-- .wrap -->


