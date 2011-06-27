<?php

if( ! class_exists( 'bbBolt_Client' ) ) :
/**
 * The bbBolt Client. Most of the work is done in the bbBolt_Client_UI singleton class. 
 * 
 * This class houses the custom settings for each client, like name & url. It then creates
 * an instance of the bbBolt_Client_UI to ensure the relevant UI elements are in place.
 * 
 * Called by @see register_bbbolt_client()
 **/
class bbBolt_Client {

	private $name;
	private $internal_name;
	private $bbbolt_url;

	public function __construct( $name, $args = array() ){

		$defaults = array(
			'labels' => array( 'name' => ucfirst( $name ) )
		);

		$args = wp_parse_args($args, $defaults);

		$this->name          = sanitize_key( $name );
		$this->labels        = (object)$args['labels'];
		$this->bbbolt_url    = $args['site_url'] . '?bbbolt';

		bbBolt_Client_UI::singleton();
	}

	public function get_name() {
		return $this->labels->name;
	}

	public function get_url() {
		return $this->bbbolt_url;
	}
}
endif;
