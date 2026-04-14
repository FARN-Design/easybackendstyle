<?php

namespace Farn\EasyBackendStyle;
use wpdb;

/**
 * This is the DatabaseConnector class which controls the interaction with the Database.
 */
class ebs_DatabaseConnector
{

    private wpdb $wpdb;
    var string $tableName;
    var string $charset_collate;

    //Defaults Values for the Database
    //If a new Variable is needed, just add the variable and default value in this map.
    var array $defaultsMap = [
        'primaryColor' => '#0073aa',
        'secondaryColor' => '#000000',
        'menuText' => '#52accc',
        'baseMenu' => '#52accc',
        'subMenu' => '#4796b3',
        'highlight' => '#096484',
        'highlightText' => '#096484',
        'notification' => '#e1a948',
        'notificationText' => '#e1a948',
        'background' => '#f0f0f0',
        'links' => '#0073aa',
        'buttons' => '#0073aa',
        'linkHover' => 'rgb(0, 149.5, 221)',
        'disabledButton' => '#dddddd',
        'disabledButtonText' => '#949494',
        'icon' => '#e5f8ff',
        'subMenuText' => '#e2ecf1',
        'deleteLinks' => '#cc1818',
        'highlightHover' => '#004f75',
        'highlightHover2' => '#002c42',
        ];

    function __construct()
    {

        global $wpdb;

        $this->wpdb = $wpdb;
        $this->tableName = $wpdb->prefix . 'easyBackendStyle';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /***
     * This function sets up the Database. This function checks if the database table from the plugin exists and if not create it.
     */
    public function setup_Database(): void
    {

        if ($this->wpdb->get_var("show tables like '" . $this->tableName . "'") != $this->tableName) {
            $sql = "CREATE TABLE " . $this->tableName . " (
	  		id mediumint(9) NOT NULL AUTO_INCREMENT,
	    	Variable varchar(128) NOT NULL UNIQUE,
	    	Value varchar(128) NOT NULL,
	    	UNIQUE KEY id (id)
	  	) $this->charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            $this->checkFields();
        }
    }

    /***
     * This function checks if all field in the database are present.
     * If not it will fill the missing field with the default values.
     */
    public function checkFields(): void
    {

        if ($this->wpdb->get_var("show tables like '" . $this->tableName . "'") != $this->tableName) {
            $this->setup_Database();
        }

        $results = $this->wpdb->get_results('SELECT * FROM ' . $this->tableName, ARRAY_N);

        $databaseVariables = array();

        for ($x = 0; $x < count($results); $x++) {
            $databaseVariables[] = $results[$x][1];
        }

        foreach ($this->defaultsMap as $key => $value) {
            if (!in_array($key, $databaseVariables)) {
                $this->wpdb->insert($this->tableName,
                    array(
                        'Variable' => $key,
                        'Value' => $value));
            }
        }
    }

    /**
     * A helper function that gets a value from a variable out of the ebs database table.
     *
     * @param string $ebs_var A String name of a variable present in the database table.
     *
     * @return array returns the result in a 2D array. $results[0][0] contains the value of the variable.
     */
    public function getValueFromDB(string $ebs_var): array
    {

        $sql = "SELECT Value FROM " . $this->tableName . " WHERE Variable = \"" . $ebs_var . "\";";
        $result = $this->wpdb->get_results($sql, ARRAY_N);

        return $result;
    }

    /**
     * A helper function that stores a new value to a variable present in the database table.
     *
     * @param string $ebs_var A String name of a variable present in the database table.
     * @param string $ebs_value The value as a String to store in the database table.
     */
    public function saveValueInDB(string $ebs_value, string $ebs_var): void
    {

        $sql = "UPDATE $this->tableName SET Value = \"" . $ebs_value . "\" WHERE Variable = \"" . $ebs_var . "\";";
        $this->wpdb->get_results($sql, ARRAY_N);
    }

    // A reset function that writes every default value to the corresponding datable table field.
    public function resetDefaults(): void
    {

        foreach ($this->defaultsMap as $key => $value) {
            $this->saveValueInDB($value, $key);
        }

    }
}
