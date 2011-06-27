<?php
/*
Plugin Name: bbBolt Client Test
Description: Imagine this as a whiz-bang plugin that wants to provide support via a bbBolt server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'bbbolt.php' );

function tp_register_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 
			'site_url'  => 'http://localhost/wp31/',
			'labels'	=> array( 'name' => 'WordPress 3.1 Site' )
		);

		register_bbbolt_client( 'wp1-test-plugin', $args );
	}	
}
add_action( 'init', 'tp_register_client' );


function tp_register_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://localhost/wp32/' );

		register_bbbolt_client( 'Test Plugin WP2', $args );
	}	
}
add_action( 'init', 'tp_register_another_client' );


function tp_register_yet_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://test.brentshepherd.com/' );

		register_bbbolt_client( 'Brent Shepherd.com', $args );
	}	
}
add_action( 'init', 'tp_register_yet_another_client' );

