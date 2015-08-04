<?php
include_once( 'forcessl.php' );
include_once( 'class-dbconnect.php' );

function sec_session_start() {
	global $dbc;
	
	$session_name = 'sec_session_id';
	$secure = SECURE;
	
	$httponly = true;
	
	$cookieParams = session_get_cookie_params();
	session_set_cookie_params( $cookieParams[ "lifetime" ],
		$cookieParams["path"],
		$cookieParams["domain"],
		$secure,
		$httponly);
		
	session_name( $session_name );
	session_start();
	session_regenerate_id( true );
}