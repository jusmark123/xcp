<?php  

/**
 * File Name: DB Connect
 * Description: Database functions
 * Version: 1.0.0
 */
 
define( "DBHOST", "db499272768.db.1and1.com" );
define( "DBNAME", "db499272768" );
define( "DBUSER", "dbo499272768" );
define( "DBPASS", "Hjvlk69a" );	
define( "SECURE", false );
define( "VERSION", '1.0.0' );

$dbc = new mysqli( DBHOST, DBUSER, DBPASS, DBNAME );

if( $dbc->connect_errno ) 
	echo "Failed to connect to MySQL: (" . $dbc->connect_errno . ") " . $dbc->connect_error;