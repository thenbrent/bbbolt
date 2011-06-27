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
		$this->site_url      = $args['site_url'];
		$this->bbbolt_url    = $args['site_url'] . '?bbbolt';

		bbBolt_Client_UI::singleton();
	}

	/** 
	 * Get the public label for this client
	 */
	public function get_name() {
		return $this->labels->name;
	}

	/** 
	 * Get different URLs on the bbBolt Server
	 * 
	 * @param $page, optional, default home, a specific page on the bbBolt Server to load
	 */
	public function get_url( $page = 'home' ) {
		return add_query_arg( 'bbbolt', $page, $this->site_url );
	}
}
endif;
