<?php
/*
Plugin Name: bbBolt
Description: Super simple support for WordPress Plugin Developers. 
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

if( ! defined( 'BBBOLT_PLUGIN_BASENAME' ) )
	define( 'BBBOLT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if( ! defined( 'BBBOLT_PLUGIN_DIR' ) )
	define( 'BBBOLT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if( ! defined( 'BBBOLT_PLUGIN_URL' ) )
	define( 'BBBOLT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-server.class.php' );
require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-client.class.php' );
require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-client-ui.class.php' );


/**
 * Register a bbBolt Server for your site. Do not use before init or with init priority less than 10.
 *
 * A function for creating a bbBolt server.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_bbbolt_server( $name = '', $args = array() ){

	$backtrace = debug_backtrace();

	if( empty( $args[ 'registering_plugin' ] ) )
		$args['registering_plugin'] = basename( dirname( $backtrace[0]['file'] ) ) . '/' . basename( $backtrace[0]['file'] );

	$bbbolt_server = new bbBolt_Server( $name = '', $args );
}


/**
 * Register a bbBolt client. Do not use before init.
 *
 * A function for creating or modifying a bbbolt client pointing to our 
 * remote WordPress site that is running the bbPress forums & acting as a
 * bbBolt Server.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_bbbolt_client( $name, $args = array() ){
	global $bbbolt_clients;

	$bbbolt_clients[] = new bbBolt_Client( $name, $args );
}
