<?php
/*
Plugin Name: bbBolt
Description: Super simple support for WordPress Plugin Developers. 
Author: Brent Shepherd
Author URI: http://find.brentshepherd.com/
Version: pre-alpha
*/

if( ! defined( 'BBBOLT_PLUGIN_BASENAME' ) )
	define( 'BBBOLT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if( ! defined( 'BBBOLT_PLUGIN_DIR' ) )
	define( 'BBBOLT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if( ! defined( 'BBBOLT_PLUGIN_URL' ) )
	define( 'BBBOLT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


function register_bbbolt_server( $name = '', $args = array() ){

	$bbbolt_server = new bbBolt_Server( $name = '', $args );
}
//add_action( 'init', 'register_bbbolt_server' );


class bbb_Server {

	private $name;
	private $internal_name;
	private $bbbolt_url;
	private $bbbolt_client;

	function __construct( $name, $args = array() ){
		
		if( empty( $name ) )
			$name = get_bloginfo( 'name' );;

		if( empty( $args['forums_url'] ) )
			$args['forums_url'] = get_site_url();

		$this->name          = $name;
		$this->internal_name = sanitize_key( strtolower( $name ) );
		$this->site_url      = $args['forums_url'];
		$this->bbbolt_url    = $args['forums_url'] . 'bbbolt/';

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init', array( &$this, 'flush_rules' ) );
		add_action( 'generate_rewrite_rules', array( &$this, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( &$this, 'request_handler' ), -1 );
		add_filter( 'status_header', array( &$this, 'unset_404' ), 10, 4 );
	}

	/**
	 * Outputs the HTML registration form for the plugin's support page.
	 *
	 * Calls the same hooks as the vanilla WordPress registration form to be compatible with 
	 * other plugins. 
	 **/
	function register_form() { 
		$user_login = '';
		$user_email = '';
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$user_login = $_POST['user_login'];
			$user_email = $_POST['user_email'];
			$errors = register_new_user($user_login, $user_email);
			if ( ! is_wp_error( $errors ) ) {
				$redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : 'wp-login.php?checkemail=registered';
				wp_safe_redirect( $redirect_to );
				exit();
			}
		}
		$redirect_to = apply_filters( 'registration_redirect', !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '' );
		?>
		<form name="registerform" id="registerform" action="<?php echo site_url('wp-login.php?action=register', 'login_post') ?>" method="post">
			<p>
				<label><?php _e('Username') ?>
				<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" tabindex="10" /></label>
			</p>
			<p>
				<label><?php _e('E-mail') ?>
				<input type="text" name="user_email" id="user_email" class="input" value="<?php echo esc_attr(stripslashes($user_email)); ?>" size="25" tabindex="20" /></label>
			</p>
			<?php do_action('register_form'); ?>
			<p id="reg_passmail"><?php _e('A password will be e-mailed to you.') ?></p>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
			<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Register'); ?>" tabindex="100" /></p>
		</form>
	<?php
	}

	function request_handler(){
		global $wp_query;

		// Don't touch non bbbolt queries
		if( 'bbbolt' != get_query_var( 'pagename' ) )
			return;

		$this->get_header();
		if( ! is_user_logged_in() ) { ?>
			<h3><?php _e( 'Login', 'bbbolt' ); ?></h3>
			<p><?php printf( __( 'To access the %s support system, you must login.', 'bbbolt' ), $this->name ); ?></p>
			<?php wp_login_form( array( 'redirect' => site_url( $_SERVER['REQUEST_URI'] ) ) ); ?>
			<a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
			<?php if( get_option('users_can_register') ) : ?>
				<h3><?php _e( 'Register', 'bbbolt' ); ?></h3>
				<p><?php printf( __( 'If you do not yet have an account with the %s support system, signup to receive convenient support.', 'bbbolt' ), $this->name ); ?></p>
				<?php $this->register_form();
			endif;
		} else { ?>
			<h3><?php _e( "Don't Panic", 'bbbolt' ); ?></h3> <?php
			require_once( dirname( __FILE__ ) . '/dont-panic.php' );
		}
		$this->get_footer();
		exit;
	}

	/* TEMPLATE FUNCTIONS */
	function get_header() {
		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<title><?php bloginfo( 'name' ); ?></title>
		</head>

		<body <?php body_class(); ?>>
	<?php
	}
	
	function get_footer() { ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Because we are hijacking the WordPress template system and delivering our own
	 * template, WordPress thinks it is a 404 request. This function tells WordPress
	 * to tell the end user that if the request if for bbbolt, it is not a 404.
	 **/
	function unset_404( $status_header, $header, $text, $protocol ) {
		global $wp_query;

		if( 'bbbolt' == get_query_var( 'pagename' ) ) {
			$status_header = "$protocol 200 OK";
			$wp_query->is_404 = false;
		}

		return $status_header;
	}

	/**
	 * Rewrite rules.
	 **/
	function rewrite_rules( $wp_rewrite ) {
		$new_rules = array( 'bbbolt/(.*)' => 'index.php?bbbolt=' . $wp_rewrite->preg_index(1) );
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}


	/**
	 * Flush rewrite rules if the bbBolt rule was not previously added.
	 **/
	function flush_rules() {
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules['bbbolt/(.+)'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	/**
	 * Add avatar query var.
	 **/
	function query_var( $vars ) {
		$vars[] = 'bbbolt';
		return $vars;
	}
}


class bbb_Client {

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


/**
 * bbBolt UI Singleton
 * 
 * Most UI functions only need to be performed once, so a singleton class is suitable and 
 * called in the bbBolt Client. 
 **/
class bbb_Client_UI {
	private static $instance;

	private function __construct() {
		add_action( 'admin_footer', array( &$this, 'support_form_slider' ) );
		add_action( 'admin_footer', array( &$this, 'print_scripts' ) );
		add_action( 'admin_print_styles', array( &$this, 'print_styles' ) );
		add_action( 'admin_menu', array( &$this, 'add_menu_page' ) );
	}

	// The singleton method
	public static function singleton() {

		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function add_menu_page(){
		global $bbbolt_admin_page;

		$bbbolt_admin_page = add_menu_page( 'Support', 'Support', 'read', 'bbbolt', array( &$this, 'support_inbox' ) );
	}

	/**
	 * 
	 **/
	public function support_inbox(){
		global $bbbolt_clients;

		$columns = array(
				'name' => 'Name',
				'subject' => 'Subject',
				'date' => 'Date'
			);
		register_column_headers( 'bbbolt-inbox', $columns );
		?>
		<div class="wrap">
			<?php screen_icon( 'users' ); ?>
			<h2><?php _e( 'Support Inbox', 'bbbolt' ); ?></h2>
			<table class="widefat">
				<thead>
					<tr><?php print_column_headers( 'bbbolt-inbox' ); ?></tr>
				</thead>
				<tfoot>
					<tr><?php print_column_headers( 'bbbolt-inbox', false ); ?></tr>
				</tfoot>
				<tbody>
				<?php
				global $wpdb;
				$sql = "SELECT * FROM $wpdb->posts where 1";
				$results = $wpdb->get_results($sql);
				if( count( $results ) > 0 ) {
					foreach( $results as $result ) {
						echo "<tr>
							<td>".$result->post_author."</td><td>".$result->post_title."</td><td>".$result->post_date."</td>
							</tr>";
					}				
				}
				?>
			</tbody>
		</table>
	</div>

	<?php
	}


	/**
	 * Output the support form for this client (or an intermediary form if there are multiple clients.)
	 **/
	public function support_form(){
		global $bbbolt_clients;

		?>
		<div id="bbb_support_form">
		<?php if ( count( $bbbolt_clients ) > 1 ) : ?>
			<?php $iframe_src = 'about:blank'; ?>
			<p><?php _e( 'Thanks for your call, to help us direct your call, please select the plugin for which you want to make a support request.', 'bbbolt' ); ?></p>
			<?php foreach( $bbbolt_clients as $client ) : ?>
				<p><a href="<?php echo $client->get_url(); ?>" target="bbbolt_frame">
					<?php echo $client->get_name(); ?>
				</a></p>
			<?php endforeach; ?>
		<?php else : ?>
		<?php $iframe_src = $bbbolt_clients[0]->bbbolt_url; ?>
		<?php endif; ?>
			<iframe id="bbbolt_frame" name="bbbolt_frame" src="<?php echo $iframe_src; ?>" width="100%">
				<p><?php _e( "Uh oh, your browser does not support iframes. Please upgrade to a modern browser.", "bbbolt") ?></p>
			</iframe>
		</div>
		<?php
	}


	/**
	 * Define the extra markup required to create a slider on every page of the 
	 * WordPress Administration.
	 **/
	public function support_form_slider() { ?>
		<div id="bbb_support_slider">
			<div id="bbb_support_toggle"><a href="#">&lt;</a></div>
			<?php $this->support_form(); ?>
		</div>
	<?php
	}


	/**
	 * To display the form in an aesthetic, usable way, we need to apply custom styles. 
	 * 
	 * This function is hooked to the admin header where it enqueues styles for bbBolt.
	 **/
	public function print_styles() { ?>
		<style>
		#bbb_support_slider {
			height: 100%;
			width:460px;
			position: fixed;
			right:-460px;
			top: 0;
		}

		#bbb_support_slider #bbb_support_toggle {
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
			z-index: 49;
		}

		#bbb_support_slider #bbb_support_toggle a {
			font-weight: bold;
			text-decoration: none;
		}

		#bbb_support_slider #bbb_support_form {
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
	 * Javascript included in the admin footer to make the bbBolt support slider more dynamic.
	 **/
	public function print_scripts() { ?>
		<script>
		jQuery(document).ready(function($) {
			$('#bbb_support_slider #bbb_support_toggle').click(function() {
				var $righty = $('#bbb_support_slider');
				$righty.animate({ right: parseInt($righty.css('right'),10) == 0 ? -$righty.outerWidth() : 0});
				$('#bbb_support_toggle a').text() == '<' ? $('#bbb_support_toggle a').text('>') : $('#bbb_support_toggle a').text('<');
			});
		});
		</script>
	<?php
	}
}

/**
 * Register a bbBolt client. Do not use before init.
 *
 * A function for creating or modifying a bbbolt client pointing to our 
 * remote WordPress site that is running the bbPress forums.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_bbbolt_client( $name, $args = array() ){
	global $bbbolt_clients;

	$bbbolt_clients[] = new bbBolt_Client( $name, $args );
}
