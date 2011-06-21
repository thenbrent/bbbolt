<?php
/*
Plugin Name: bbBolt Client Test
Description: Imagine this as a whiz-bang plugin that wants to provide support via a bbBolt server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'bbbolt.php' );

function tp_register_bbbolt_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'forums_url' => 'http://localhost/wp31/' );

		register_bbbolt_client( 'Test Plugin', $args );
	}	
}
add_action( 'init', 'tp_register_bbbolt_client' );

