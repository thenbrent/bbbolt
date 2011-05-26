<?php
/*
Plugin Name: Tyrolean
Description: Super simple support for WordPress Plugin Developers. 
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

if( ! defined( 'TYROLEAN_PLUGIN_BASENAME' ) )
	define( 'TYROLEAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if( ! defined( 'TYROLEAN_PLUGIN_DIR' ) )
	define( 'TYROLEAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if( ! defined( 'TYROLEAN_PLUGIN_URL' ) )
	define( 'TYROLEAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


function register_tyrolean_server( $args = array() ){

	if( empty( $forums_urls ) )
		$forums_urls = get_site_url();

	$tyrolean_server = new Tyrolean_Server( $forums_urls, $args );
}
add_action( 'init', 'register_tyrolean_server' );

/**
 * Register a Tyrolean client. Do not use before init.
 *
 * A function for creating or modifying a tyrolean client pointing to our 
 * remote WordPress site that is running the central bbPress forums.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 *
 *
 **/
function register_tyrolean_client( $args = array() ){
	$tyrolean_client = new Tyrolean_Client( $args );
}

class Tyrolean_Server {

	private $forums_url;
	private $tyrolean_client;

	function __construct( $args = array() ){

		$this->forums_url = $args['forums_url'];

		//$this->tyrolean_client = new Tyrolean_Client( $this->forums_url );

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init', array( &$this, 'flush_rules' ) );
		add_action( 'generate_rewrite_rules', array( &$this, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( &$this, 'request_handler' ), -1 );
	}


	function request_handler(){
		global $wp_query;
		if( 'tyrolean' == get_query_var( 'pagename' ) || $id = get_query_var( 'tyrolean' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/dont_panic.php' );
			exit;
		}
	}


	/**
	 * rewrite rules.
	 **/
	function rewrite_rules( $wp_rewrite ) {
		$new_rules = array( 'tyrolean/(.+)' => 'index.php?tyrolean=' . $wp_rewrite->preg_index(1) );
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		//error_log( '$wp_rewrite->rules = ' . print_r( $wp_rewrite->rules, true ) );
	}


	/**
	 * Flush rewrite rules if the Tyrolean rule was not previously added.
	 **/
	function flush_rules() {
		$rules = get_option( 'rewrite_rules' );
		//error_log( '$rules = ' . print_r( $rules, true ) );

		if ( ! isset( $rules['tyrolean/(.+)'] ) ) {
			error_log( 'flushing rules' );
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	/**
	 * Add avatar query var.
	 **/
	function query_var( $vars ) {
		$vars[] = 'tyrolean';
		return $vars;
	}
	
}


class Tyrolean_Client {

	private $forums_url;
	private $forums_form_url;

	function __construct( $args = array() ){

		$this->forums_url      = $args['forums_url'];
		$this->forums_form_url = $this->forums_url . 'tyrolean/098234098234/';

		add_action( 'admin_footer', array( &$this, 'support_form_slider' ) );
		add_action( 'admin_footer', array( &$this, 'print_scripts' ) );
		add_action( 'admin_print_styles', array( &$this, 'print_styles' ) );
		add_action( 'admin_menu', array( &$this, 'add_menu_page' ) );
	}

	function add_menu_page(){
		add_menu_page( 'Support', 'Support', 'read', 'tyrolean', array( &$this, 'support_form' ) );
	}

	function support_form(){
		?>
		<div id="ty_support_form">
			<h3><?php _e( "Don't Panic", "tyrolean") ?></h3>
			<iframe src="<?php echo $this->forums_form_url; ?>" width="100%" height="700">
				<p><?php _e( "Uh oh, your browser does not support iframes. Please upgrade to a modern browser.", "tyrolean") ?></p>
			</iframe>
		</div>
	<?php
	}

	/**
	 * Define the extra markup required to create a slider on every page of the 
	 * WordPress Administration.
	 **/
	function support_form_slider() { ?>
		<div id="ty_support_slider">
			<div id="ty_support_toggle"><a href="#"><?php _e( "Don't Panic", 'tyrolean' ); ?></a></div>
			<?php $this->support_form(); ?>
		</div>
	<?php
	}

	/**
	 * To display the form in an aesthetic, usable way, we need to apply custom styles. 
	 * 
	 * This function is hooked to the admin header where it enqueues styles for Tyrolean.
	 **/
	function print_styles() { ?>
		<style>
		#ty_support_slider {
			height: 100%;
			width:460px;
			position: fixed;
			right:-460px;
			top: 0;
		}
		#ty_support_slider #ty_support_toggle {
			position: relative;
			left: -50px;
			top: 50%;
			width: 70px;
			height: 1.5em;
			background: #c4f500;
			padding: 2px 6px;
			-moz-box-shadow: 0px 0px 6px #888;
			-webkit-box-shadow: 0px 0px 6px #888;
			box-shadow: 0px 0px 6px #888;
			-webkit-transform: rotate(-90deg);
			-moz-transform: rotate(-90deg);
			filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
		}
		#ty_support_slider #ty_support_toggle a {
			text-decoration: none;
		}
		#ty_support_slider #ty_support_form {
			background: #efefef;
			border: 1px solid #aaa;
			height: 100%;
			padding: 10px;
			position: relative;
			top:0;
			left:0;
			-moz-box-shadow:inset 2px 0px 5px #aaa;
			-webkit-box-shadow:inset 2px 0px 5px #aaa;
			box-shadow:inset 2px 0px 5px #aaa;
		}
		</style>
	<?php
	}

	/**
	 * Javascript included in the admin footer to make the Tyrolean support slider more dynamic.
	 **/
	function print_scripts() { ?>
		<script>
		jQuery(document).ready(function($) {
			$('#ty_support_slider #ty_support_toggle').click(function() {
				var $righty = $('#ty_support_slider');
				$righty.animate({ right: parseInt($righty.css('right'),10) == 0 ? -$righty.outerWidth() : 0});
			});
		});
		</script>
	<?php
	}
}
