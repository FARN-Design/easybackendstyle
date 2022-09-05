<?php

require_once('easyBackendStyle.php');
require_once('ebs_DatabaseConnector.php');

if (isset($_REQUEST['submit'])) {

    saveValueInDB($_REQUEST['menuText'],'menuText');
    saveValueInDB($_REQUEST['baseMenu'], 'baseMenu');
    saveValueInDB($_REQUEST['subMenu'], 'subMenu');
    saveValueInDB($_REQUEST['highlight'], 'highlight');
    saveValueInDB($_REQUEST['notification'], 'notification');
    saveValueInDB($_REQUEST['background'], 'background');
    saveValueInDB($_REQUEST['links'], 'links');
    saveValueInDB($_REQUEST['buttons'], 'buttons');
    saveValueInDB($_REQUEST['formInputs'], 'formInputs');

    my_custom_fonts();
}

if (isset($_REQUEST['submitCustomCSS'])) {

    saveValueInDB($_REQUEST['customCSS'], 'customCSS');

    add_custom_css();
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
                        value="<?php echo getValueFromDB("menuText")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="baseMenu">baseMenu</label></th>
                    <td>
                        <input type="color" name="baseMenu" id="baseMenu" class="small-text"
                        value="<?php echo getValueFromDB("baseMenu")[0][0]; ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="subMenu">subMenu</label></th>
                    <td>
                        <input type="color" name="subMenu" id="subMenu" class="small-text"
                        value="<?php echo getValueFromDB("subMenu")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="highlight">highlight</label></th>
                    <td>
                        <input type="color" name="highlight" id="highlight" class="small-text"
                        value="<?php echo getValueFromDB("highlight")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="notification">notification</label></th>
                    <td>
                        <input type="color" name="notification" id="notification" class="small-text"
                        value="<?php echo getValueFromDB("notification")[0][0];?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="background">background</label></th>
                    <td>
                        <input type="color" name="background" id="background" class="small-text"
                        value="<?php echo getValueFromDB("background")[0][0];?>">
                    </td>
                </tr>
               
                <tr>
                    <th scope="row"><label for="links">links</label></th>
                    <td>
                        <input type="color" name="links" id="links" class="small-text"
                        value="<?php echo getValueFromDB("links")[0][0];?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="buttons">buttons</label></th>
                    <td>
                        <input type="color" name="buttons" id="buttons" class="small-text"
                        value="<?php echo getValueFromDB("buttons")[0][0];?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="formInputs">formInputs</label></th>
                    <td>
                        <input type="color" name="formInputs" id="formInputs" class="small-text"
                        value="<?php echo getValueFromDB("formInputs")[0][0];?>">
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="submit" class="button button-primary" name="submit" id="submit">
    </form>

    <form action="" method="post">
        <table>
            <tbody>
                <tr>
                    <th scope="row"><label for="customCSS">customCSS</label></th>
                    <td>
                        <textarea name="customCSS" id="customCSS"
                        value=""><?php echo getValueFromDB("customCSS")[0][0];?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="submit" class="button button-primary" name="submitCustomCSS" id="submitCustomCSS">
    </form>

</div><!-- .wrap -->

