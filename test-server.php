<?php
/*
Plugin Name: bbBolt Server Test
Description: Demonstration code for setting up a bbBolt Server or a plugin to setup a bbBolt server with default settings.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'bbbolt.php' );

function register_test_bbbolt_server(){

	if( function_exists( 'register_bbbolt_server' ) ){
		$bbbolt_server = register_bbbolt_server( 'bbb-test-server' );
	}	
}
add_action( 'init', 'register_test_bbbolt_server' );
