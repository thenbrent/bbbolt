<?php
/*
Plugin Name: bbBolt Example Clients
Description: This plugin registers two example clients for your tire-kicking pleasure. The bbBolt Servers are located at <a href="http://demo.bbbolt.org">demo.bbbolt.org</a> and <a href="http://bbbolt.brentshepherd.com">bbbolt.brentshepherd.com</a>. 
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: beta-1
*/

/**
 * Imagine this as a whiz-bang plugin that wants to offer premium support via a bbBolt server.
 * 
 * We first need the bbBolt Client Class. 
 */
require_once( 'bbbolt-client.class.php' );


/**
 * Now we can create our client. 
 * As the parameters explain, this client is called 'bbBolt Demo' and uses the bbBolt Server 
 * active at demo.bbbolt.org.
 */
function eg_register_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://demo.bbbolt.org/' );

		register_bbbolt_client( 'bbBolt Demo', $args );
	}	
}
add_action( 'init', 'eg_register_client' );


/**
 * Another example for registering a bbBolt Client just so the UI shows what life is like with
 * multiple clients active on the one site.
 */
function ep_register_another_client(){
	if( function_exists( 'register_bbbolt_client' ) ){
		$args = array( 'site_url' => 'http://bbbolt.brentshepherd.com/' );

		register_bbbolt_client( "Brent's bbBolt Server", $args );
	}	
}
add_action( 'init', 'ep_register_another_client' );

