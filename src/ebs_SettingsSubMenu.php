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

        // RegEx-String Replacement to insert variable for highlighted Text+Icons
        $newContent = preg_replace(
                [
                        '/(#adminmenu li\.wp-has-current-submenu div\.wp-menu-image:before,\n#adminmenu a\.current:hover div\.wp-menu-image:before,\n#adminmenu li\.current div\.wp-menu-image:before,\n#adminmenu li\.wp-has-current-submenu a:focus div\.wp-menu-image:before,\n#adminmenu li\.wp-has-current-submenu\.opensub div\.wp-menu-image:before,\n#adminmenu li:hover div\.wp-menu-image:before,\n#adminmenu li a:focus div\.wp-menu-image:before,\n#adminmenu li\.opensub div\.wp-menu-image:before \{\n  color: )#fff(;)/',
                        '/(#adminmenu \.menu-counter,\s*#adminmenu \.awaiting-mod,\s*#adminmenu \.update-plugins\s*\{\s*color:\s*)#fff(;)/',
                        '/(#adminmenu li\.current a\.menu-top,\s*#adminmenu li\.wp-has-current-submenu a\.wp-has-current-submenu,\s*#adminmenu li\.wp-has-current-submenu \.wp-submenu \.wp-submenu-head,\s*.folded #adminmenu li\.current\.menu-top\s*\{\s*color:\s*)#fff(;)/',
                        '/(#adminmenu a:hover,\n#adminmenu li\.menu-top:hover,\n#adminmenu li\.opensub > a\.menu-top,\n#adminmenu li > a\.menu-top:focus \{\n  color: )#fff(;)/',
                        '/(#adminmenu li\.menu-top:hover div\.wp-menu-image:before,\n#adminmenu li\.opensub > a\.menu-top div\.wp-menu-image:before \{\n  color: )#fff(;)/',
                ],
                [
                        '$1var(--ebsHighlightedText)$2',
                        '$1var(--ebsHighlightedText)$2',
                        '$1var(--ebsHighlightedText)$2',
                        '$1var(--ebsHighlightedText)$2',
                        '$1var(--ebsHighlightedText)$2'
                ], $newContent
        );
        foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue) {
            foreach ($colorValue[1] as $oldColor) {
                $newContent = str_replace($oldColor, "var(--".$colorKey.")", $newContent);
            }
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

            foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue) {
                $this->dbc->saveValueInDB($_REQUEST[$colorKey], $colorKey);
            }
            $this->generateColorsCss();
        }

        if (isset($_REQUEST['resetDefaults'])) {
            $this->dbc->resetDefaults();
        }

        // TODO: Fresh als Farbschema nicht mehr verfügbar auf WP - aktualisieren mit blue?
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

            foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue){
                    if($colorKey != "ebsPrimary" && $colorKey != "ebsSecondary" && $colorKey != "ebsTertiary"){
                        continue;
                    }
                    if(in_array($colorKey, $mainColors)){
                        continue;
                    }
                    $mainColors[] = $colorKey;?>
                <div class="wrapper_<?php echo $colorKey; ?>">
                    <label for="<?php echo $colorKey; ?>"> <?php echo $colorValue[0]; ?> </label>
                    <input type="text" name="<?php echo $colorKey; ?>" id="<?php echo $colorKey; ?>"
                           value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($colorKey)[0][0]); ?>"
                           class="ebs_mainColorPicker"
                           data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($colorKey)[0][0]); ?>"/>
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

                    foreach ($GLOBALS['ebsColorMapping'] as $colorKey => $colorValue){
                        if($colorKey == "ebsPrimary" || $colorKey == "ebsSecondary" || $colorKey == "ebsTertiary"){
                            continue;
                        }
                        if(in_array($colorKey, $advancedColorFields)){
                            continue;
                        }
                        $advancedColorFields[] = $colorKey;?>
                        <div class="wrapper_<?php echo $colorKey; ?>">
                            <label for="<?php echo $colorKey; ?>"> <?php echo $colorValue[0]; ?> </label>
                            <input type="text" name="<?php echo $colorKey; ?>" id="<?php echo $colorKey; ?>"
                                   value="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($colorKey)[0][0]); ?>"
                                   class="ebs_colorPicker"
                                   data-default-color="<?php echo esc_attr($GLOBALS['ebsPlugin']->dbc->getValueFromDB($colorKey)[0][0]); ?>"/>
                        </div>
                    <?php } ?>
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


