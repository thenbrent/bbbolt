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

	if( empty( $args['forums_url'] ) )
		$args['forums_url'] = get_site_url();

	$tyrolean_server = new Tyrolean_Server( $args );
}
add_action( 'init', 'register_tyrolean_server' );

/**
 * Register a Tyrolean client. Do not use before init.
 *
 * A function for creating or modifying a tyrolean client pointing to our 
 * remote WordPress site that is running the bbPress forums.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_tyrolean_client( $args = array() ){
	$tyrolean_client = new Tyrolean_Client( $args );
}


class Tyrolean_Server {

	private $tyrolean_url;
	private $tyrolean_client;

	function __construct( $args = array() ){

		$this->site_url     = $args['forums_url'];
		$this->tyrolean_url = $args['forums_url'] . 'tyrolean/';

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init', array( &$this, 'flush_rules' ) );
		add_action( 'generate_rewrite_rules', array( &$this, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( &$this, 'request_handler' ), -1 );
		add_filter( 'status_header', array( &$this, 'unset_404' ), 10, 4 );
	}

	/**
	 * Because we are hijacking the WordPress template system and delivering our own
	 * template, WordPress thinks it is a 404 request. This function tells WordPress
	 * to tell the end user that if the request if for tyrolean, it is not a 404.
	 **/
	function unset_404( $status_header, $header, $text, $protocol ) {
		global $wp_query;

		if( 'tyrolean' == get_query_var( 'pagename' ) )
			$status_header = "$protocol 200 OK";

		return $status_header;
	}

	function request_handler(){
		global $wp_query;

		if( 'tyrolean' == get_query_var( 'pagename' ) ) {
			require_once( dirname( __FILE__ ) . '/dont-panic.php' );
			exit;
		}
	}

	/**
	 * Rewrite rules.
	 **/
	function rewrite_rules( $wp_rewrite ) {
		$new_rules = array( 'tyrolean/(.*)' => 'index.php?tyrolean=' . $wp_rewrite->preg_index(1) );
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}


	/**
	 * Flush rewrite rules if the Tyrolean rule was not previously added.
	 **/
	function flush_rules() {
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules['tyrolean/(.+)'] ) ) {
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

	private $tyrolean_url;

	function __construct( $args = array() ){

		$this->tyrolean_url = $args['forums_url'] . 'tyrolean/';

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
			<iframe id="tyrolean_frame" name="tyrolean_frame" src="<?php echo $this->tyrolean_url; ?>" width="100%" height="100%">
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
			<div id="ty_support_toggle"><a href="#"><</a></div>
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
			background: #ECECEC;
			border: 1px solid #CCC;
			width: 10px;
			height: 30px;
			float: left;
			position: relative;
			left: -13px;
			top: 50%;
			padding: 15px 0px 0px 2px;
			-webkit-border-top-left-radius: 6px;
			-webkit-border-bottom-left-radius: 6px;
			-moz-border-radius-topleft: 6px;
			-moz-border-radius-bottomleft: 6px;
			border-top-left-radius: 6px;
			border-bottom-left-radius: 6px;
			box-shadow: 0px 0px 5px #AAA;
			-moz-box-shadow: 0px 0px 5px #AAA;
			-webkit-box-shadow: 0px 0px 5px #AAA;
			z-index: 51;
		}

		#ty_support_slider #ty_support_toggle a {
			font-weight: bold;
			text-decoration: none;
		}

		#ty_support_slider #ty_support_form {
			background: #ECECEC;
			border: 1px solid #CCC;
			height: 100%;
			padding: 10px;
			position: relative;
			top:0;
			left:0;
			-moz-box-shadow:inset 2px 0px 5px #CCC;
			-webkit-box-shadow:inset 2px 0px 5px #CCC;
			box-shadow:inset 2px 0px 5px #CCC;
			z-index: 50;
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
				$('#ty_support_toggle a').text() == '<' ? $('#ty_support_toggle a').text('>') : $('#ty_support_toggle a').text('<');
			});
		});
		</script>
	<?php
	}
}
