<?php

function setup_Database(){
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
  	$table_name = $wpdb->prefix . 'easyBackendStyle';

  	if (in_array($table_name, $wpdb->tables)) {
  		return;
  	}

  	$sql = "CREATE TABLE $table_name (
  		id mediumint(9) NOT NULL AUTO_INCREMENT,
    	colorVariable varchar(255) NOT NULL,
    	colorValue varchar(255) NOT NULL,
    	UNIQUE KEY id (id)
  	) $charset_collate;";

	  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	  dbDelta( $sql );

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

?>