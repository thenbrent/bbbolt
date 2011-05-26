<?php
/*
Plugin Name: Tyrolean Client Test
Description: Imagine this as a whiz-bang plugin that wants to provide support via a Tyrolean server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'tyrolean.php' );

function tp_register_tyrolean_client(){
	if( function_exists( 'register_tyrolean_client' ) ){
		$args = array( 'forums_url' => 'http://localhost.localdomain/wp31/' );

		register_tyrolean_client( $args );
	}	
}
add_action( 'init', 'tp_register_tyrolean_client' );
