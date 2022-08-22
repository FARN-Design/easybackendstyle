<?php

function setup_Database(){

	global $wpdb;

	$wpdb->show_errors();

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'easyBackendStyle';

  	if ($wpdb->get_var("show tables like '" . $table_name . "'") != $table_name) {
  		$sql = "CREATE TABLE $table_name (
  		id mediumint(9) NOT NULL AUTO_INCREMENT,
    	colorVariable varchar(255) NOT NULL,
    	colorValue varchar(255) NOT NULL,
    	UNIQUE KEY id (id)
  	) $charset_collate;";

	  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	  dbDelta( $sql );

	 /**
  	 *	Setup default values
  	 */ 
	  $wpdb->insert($table_name, 
	    array( 
	      'colorVariable' => 'backgroundMenuColor',
	      'colorValue' => $GLOBALS['backgroundMenuColor']
	    )
	  );

	  $wpdb->insert($table_name, 
	    array( 
	      'colorVariable' => 'selectedMenuColor',
	      'colorValue' => $GLOBALS['selectedMenuColor']
	    )
	  );

	  $wpdb->insert($table_name, 
	    array( 
	      'colorVariable' => 'hoverMenuColor',
	      'colorValue' => $GLOBALS['hoverMenuColor']
	    )
	  );

	  $wpdb->insert($table_name, 
	    array( 
	      'colorVariable' => 'menuTextColor',
	      'colorValue' => $GLOBALS['menuTextColor']
	    )
	  );

	  $wpdb->insert($table_name, 
	    array( 
	      'colorVariable' => 'menuTextHoverColor',
	      'colorValue' => $GLOBALS['menuTextHoverColor']
	    )
	  );
    }
}

function getColorValueFromDB($ebs_var){

	global $wpdb;
	$table_name = $wpdb->prefix . 'easyBackendStyle';

	$sql = "SELECT colorValue FROM wp_easyBackendStyle WHERE colorVariable = \"" . $ebs_var ."\";";

	$result = $wpdb->get_results($sql, ARRAY_N);

	return $result;
}

function saveColorValueInDB($ebs_value, $ebs_var){
	global $wpdb;
	$table_name = $wpdb->prefix . 'easyBackendStyle';

	$sql = "UPDATE wp_easyBackendStyle SET colorValue = \"". $ebs_value ."\" WHERE colorVariable = \"". $ebs_var ."\";";

	$result = $wpdb->get_results($sql, ARRAY_N);

	return $result;
}




?>