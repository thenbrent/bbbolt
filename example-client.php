<?php
/*
Plugin Name: bbBolt Example Clients
Description: Imagine this as a whiz-bang plugin that wants to premium provide support via a bbBolt server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: beta-1
*/

require_once( 'bbbolt-client.class.php' );


/**
 * An example of registering a bbBolt Client
 */
function eg_register_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://demo.bbbolt.org/' );

		register_bbbolt_client( 'bbBolt.org Demo Support', $args );
	}	
}
add_action( 'init', 'eg_register_client' );


/**
 * Another example for registering a bbBolt Client so the UI shows multiple clients
 */
function ep_register_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://test.brentshepherd.com/' );

		register_bbbolt_client( "Brent's bbBolt Server", $args );
	}	
}
add_action( 'init', 'ep_register_another_client' );

