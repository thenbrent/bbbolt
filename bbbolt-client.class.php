<?php


/**
 * Class 
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


