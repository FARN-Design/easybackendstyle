<?php

require_once('easyBackendStyle.php');
require_once('ebs_DatabaseConnector.php');

if (isset($_REQUEST['submit'])) {

    saveColorValueInDB($_REQUEST['menuText'],'menuText');
    saveColorValueInDB($_REQUEST['baseMenu'], 'baseMenu');
    saveColorValueInDB($_REQUEST['subMenu'], 'subMenu');
    saveColorValueInDB($_REQUEST['highlight'], 'highlight');
    saveColorValueInDB($_REQUEST['notification'], 'notification');
    saveColorValueInDB($_REQUEST['background'], 'background');
    saveColorValueInDB($_REQUEST['links'], 'links');
    saveColorValueInDB($_REQUEST['buttons'], 'buttons');
    saveColorValueInDB($_REQUEST['formInputs'], 'formInputs');

    my_custom_fonts();
}
?>

<div class="wrap">

    <h1>Settings for EasyBackendStyle Plugin</h1>
    <p class="description">
        This is the settings Page for the EasyBackendStyle Plugin.
    </p>

    <form action="" method="post">

        <table>
            <tbody>
                <tr>
                    <th scope="row"><label for="menuText">menuText</label></th>
                    <td>
                        <input type="color" name="menuText" id="menuText" class="small-text"
                        value="<?php echo getColorValueFromDB("menuText")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="baseMenu">baseMenu</label></th>
                    <td>
                        <input type="color" name="baseMenu" id="baseMenu" class="small-text"
                        value="<?php echo getColorValueFromDB("baseMenu")[0][0]; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="subMenu">subMenu</label></th>
                    <td>
                        <input type="color" name="subMenu" id="subMenu" class="small-text"
                        value="<?php echo getColorValueFromDB("subMenu")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="highlight">highlight</label></th>
                    <td>
                        <input type="color" name="highlight" id="highlight" class="small-text"
                        value="<?php echo getColorValueFromDB("highlight")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="notification">notification</label></th>
                    <td>
                        <input type="color" name="notification" id="notification" class="small-text"
                        value="<?php echo getColorValueFromDB("notification")[0][0];?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="background">background</label></th>
                    <td>
                        <input type="color" name="background" id="background" class="small-text"
                        value="<?php echo getColorValueFromDB("background")[0][0];?>">
                    </td>
                </tr>
               
                <tr>
                    <th scope="row"><label for="links">links</label></th>
                    <td>
                        <input type="color" name="links" id="links" class="small-text"
                        value="<?php echo getColorValueFromDB("links")[0][0];?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="buttons">buttons</label></th>
                    <td>
                        <input type="color" name="buttons" id="buttons" class="small-text"
                        value="<?php echo getColorValueFromDB("buttons")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="formInputs">formInputs</label></th>
                    <td>
                        <input type="color" name="formInputs" id="formInputs" class="small-text"
                        value="<?php echo getColorValueFromDB("formInputs")[0][0];?>">
                    </td>
                </tr>
            </tbody>
        </table>
        
    <?php submit_button( $name = 'Save' ); ?> </form>

</div><!-- .wrap -->

