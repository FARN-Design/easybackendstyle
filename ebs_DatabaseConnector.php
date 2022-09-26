<?php

class DatabaseConnector{

	var $test = 'test';
	private $wpdb;
	var $tableName;
	var $charset_collate;

	function __construct(){

		global $wpdb;

		$this->wpdb = $wpdb;
		$this->tableName = $wpdb->prefix . 'easyBackendStyle';
		$this->charset_collate = $wpdb->get_charset_collate();
	}

	public function logInfo(){
		return $this->wpdb;
	}

	public function setup_Database(){

	  	if ($this->wpdb->get_var("show tables like '" . $this->tableName . "'") != $this->tableName) {
	  		$sql = "CREATE TABLE $this->tableName (
	  		id mediumint(9) NOT NULL AUTO_INCREMENT,
	    	Variable varchar(255) NOT NULL UNIQUE,
	    	Value varchar(255) NOT NULL,
	    	UNIQUE KEY id (id)
	  	) $this->charset_collate;";

		  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		  dbDelta( $sql );

		  checkFields();
		}
	}

	// TODO Check if Database exists
	public function checkFields(){

		$results = $this->wpdb->get_results('SELECT * FROM wp_easyBackendStyle', ARRAY_N);

		$databaseVariables = array();

		for ($x = 0; $x < count($results); $x++) {
			$databaseVariables[] = $results[$x][1];
		}

		//add new option here to check if its in the database
		$defaultsMap = [
			'menuText' => '#f0f0f1',
			'baseMenu' => '#1d2327',
			'subMenu' => '#2c3338',
			'highlight' => '#2271b1',
			'notification' => '#d63638',
			'background' => '#f0f0f1',
			'links' => '#2271b1',
			'buttons' => '#2271b1',
			'formInputs' => '#3582c4',
			'customCSS' => '<style></style>'];

		foreach ($defaultsMap as $key => $value) {
			if(!in_array($key, $databaseVariables)){
			  $this->wpdb->insert($this->tableName, 
		    	array( 
		      	'Variable' => $key,
		      	'Value' => $value ));
			}
		}
	}

	public function getValueFromDB($ebs_var){

		$sql = "SELECT Value FROM wp_easyBackendStyle WHERE Variable = \"" . $ebs_var ."\";";
		$result = $this->wpdb->get_results($sql, ARRAY_N);

		return $result;
	}

	public function saveValueInDB($ebs_value, $ebs_var){
	
		$sql = "UPDATE wp_easyBackendStyle SET Value = \"". $ebs_value ."\" WHERE Variable = \"". $ebs_var ."\";";
		$result = $this->wpdb->get_results($sql, ARRAY_N);

		return $result;
	}
}
?>