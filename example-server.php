<?php
/*
Plugin Name: bbBolt Example Server
Description: Demonstration of a bbBolt Server running on the site where this plugin is activate.
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: beta-1
*/


require_once( 'bbbolt-server.class.php' );


function eg_register_bbbolt_server(){

	if( function_exists( 'register_bbbolt_server' ) ){
		// IMPORTANT: For security, you should store your API credentials in your site's database or wp-config.php.
		// Storing real credentials in a source code file like this is not a good idea.
		$paypal_credentials = array(
			'username'  => 'digita_1308916325_biz_api1.gmail.com',
			'password'  => '1308916362',
			'signature' => 'AFnwAcqRkyW0yPYgkjqTkIGqPbSfAyVFbnFAjXCRltVZFzlJyi2.HbxW'
		);

		$args['paypal']['subscription']['amount'] = 5.90;
		$args['paypal']['subscription']['initial_amount'] = 29.00; 

		register_bbbolt_server( 'bbb-test-server', $paypal_credentials, $args );
	}	
}
add_action( 'init', 'eg_register_bbbolt_server', 12 );

