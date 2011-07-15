<?php


if( ! class_exists( 'bbBolt_Client_UI' ) )
	require_once( 'bbbolt-client-ui.class.php' );


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
			'labels' => array( 'name' => ucfirst( $name ), 'singular_name' => ucfirst( $name ) )
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


	/**
	 * Returns the URL to the location of this file's parent folder.
	 * 
	 * Useful for enqueuing scripts, styles & images without hardcoding the URL. 
	 * Allows the bbbolt directory to be located anywhere in a plugin.
	 */
	public function get_dir_url() {
		$path_after_plugin_dir = explode( 'plugins', dirname( __FILE__ ) );
		return plugins_url() . $path_after_plugin_dir[1];
	}
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
 * @param $name (string)(required) the name of your plugin.
 * @param $args (array) - an array of named arguments including:
 * 			'site_url' (required) - the URL of your remote bbBolt Server
 * 			'labels' (optional)(array) - associative array of labels for the client, currently only support 'name' & 'singular_name'
 **/
function register_bbbolt_client( $name, $args = array() ){
	global $bbbolt_clients;

	// If you are using a custom bbBolt Server Class, hook into this filter & return your new class when $name is == your client
	$bbbolt_client_class = apply_filters( 'bbBolt_Client_Class', 'bbBolt_Client', $name );

	$bbbolt_clients[] = new $bbbolt_client_class( $name, $args );
}
endif;

