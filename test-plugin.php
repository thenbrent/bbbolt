<?php
/*
Plugin Name: Tyrolean Client Test
Description: Imagine this as a whiz-bang plugin that wants to provide support via a Tyrolean server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'tyrolean.php' );

/**
 * Create a tyrolean client pointing to our remote WordPress site that 
 * is running the central bbPress forums
 **/
//$tyrolean_client = new Tyrolean_Client( 'http://localhost.localdomain/wp31/' );


function test_register_tyrolean_client(){
	if( function_exists( 'register_tyrolean_client' ) ){
		register_tyrolean_client( 'http://localhost.localdomain/wp31/' );
	}	
}
add_action( 'init', 'test_register_tyrolean_client' );
