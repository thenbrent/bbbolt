<?php
/*
bbBolt
Subscription Driven Support for WordPress Plugin Developers.
Author: Leonard's Ego
Author URI: http://leonardsego.com/
Version: beta-1
Copyright: Leonard's Ego Pty. Ltd.
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


if( ! defined( 'BBBOLT_PLUGIN_DIR' ) )
	define( 'BBBOLT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


if( ! class_exists( 'bbBolt_Server' ) )
	require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-server.class.php' );

if( ! class_exists( 'bbBolt_Client' ) )
	require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-client.class.php' );

if( ! class_exists( 'bbBolt_Client_UI' ) )
	require_once( BBBOLT_PLUGIN_DIR . 'bbbolt-client-ui.class.php' );


if( ! function_exists( 'register_bbbolt_server' ) ) :
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
endif;


if( ! function_exists( 'register_bbbolt_client' ) ) :
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
endif;


if( ! function_exists( 'get_bbbolt_dir_url' ) ) :
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
endif;
