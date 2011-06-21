<?php


/**
 * Class 
 **/
class bbBolt_Client {

	private $name;
	private $internal_name;
	private $bbbolt_url;

	public function __construct( $name, $args = array() ){

		$this->name          = $name;
		$this->internal_name = sanitize_key( strtolower( $name ) );
		$this->bbbolt_url    = $args['forums_url'] . 'bbbolt/';

		bbBolt_Client_UI::singleton();
	}
	
	public function get_name() {
		return $this->name;
	}

	public function get_url() {
		return $this->bbbolt_url;
	}
}


