<?php
/*
 bbBolt
 Super simple support for WordPress Plugin Developers. 
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
 * Register a bbBolt Server for your site. Do not use before init or with init priority later than 10.
 *
 * A function for creating a bbBolt server.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_bbbolt_server( $name, $args = array() ){

	// If you are using a custom bbBolt Server Class, hook into this filter
	$bbbolt_server_class = apply_filters( 'bbBolt_Server_Class', 'bbBolt_Server' );

	$bbbolt_server = new $bbbolt_server_class( $name, $args );
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

	// If you are using a custom bbBolt Server Class, hook into this filter
	$bbbolt_client_class = apply_filters( 'bbBolt_Client_Class', 'bbBolt_Client' );

	$bbbolt_clients[] = new $bbbolt_client_class( $name, $args );
}


/**
 * Returns the URL to the location of this file's parent folder.
 * 
 * Useful for enqueuing scripts, styles & images without hardcoding the URL. 
 * Allows the bbbolt directory to be located anywhere in a plugin.
 * 
 */
function get_bbbolt_dir_url() {
	$path_after_plugin_dir = explode( 'plugins', dirname( __FILE__ ) );
	return plugins_url() . $path_after_plugin_dir[1];
}
