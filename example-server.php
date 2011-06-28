<?php
/*
Plugin Name: bbBolt Example Server
Description: A demonstration code for setting up a bbBolt Server.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

require_once( 'bbbolt.php' );

function register_test_bbbolt_server(){

	// IMPORTANT: For security, you should store your API credentials in your site's
	// database or wp-config.php. Storing real credentials in a source code file like
	// this is not a good idea.
	$paypal_credentials = array(
		'username'  => 'digita_1308916325_biz_api1.gmail.com',
		'password'  => '1308916362',
		'signature' => 'AFnwAcqRkyW0yPYgkjqTkIGqPbSfAyVFbnFAjXCRltVZFzlJyi2.HbxW'
	);

	if( function_exists( 'register_bbbolt_server' ) ){
		$bbbolt_server = register_bbbolt_server( 'bbb-test-server', $paypal_credentials );
	}	
}
add_action( 'init', 'register_test_bbbolt_server' );
