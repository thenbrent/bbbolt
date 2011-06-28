<?php
/*
Plugin Name: bbBolt Example Clients
Description: Imagine this as a whiz-bang plugin that wants to premium provide support via a bbBolt server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: beta-1
*/

require_once( 'bbbolt.php' );

function ep_register_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 
			'site_url'  => 'http://localhost/wp31/',
			'labels'	=> array( 'name' => 'Whiz Bang Plugin' )
		);

		register_bbbolt_client( 'wicked-sick-plugin', $args );
	}	
}
//add_action( 'init', 'ep_register_client' );


function ep_register_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://localhost/wp32/' );

		register_bbbolt_client( 'Wicked Sick Plugin 3.2', $args ); 
	}	
}
//add_action( 'init', 'ep_register_another_client' );


function ep_register_yet_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://test.brentshepherd.com/' );

		register_bbbolt_client( 'Remote bbBolt Server', $args );
	}	
}
add_action( 'init', 'ep_register_yet_another_client' );

