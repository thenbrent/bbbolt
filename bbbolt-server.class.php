<?php

/**
 * Class 
 **/
class bbBolt_Server {

	private $name;			
	private $internal_name;	
	private $bbbolt_url;	
	private $bbbolt_client;	
	private $registering_plugin;	// The plugin which has registered the server

	function __construct( $name, $args = array() ){

		if( empty( $name ) )
			$name = get_bloginfo( 'name' );;

		if( empty( $args['site_url'] ) )
			$args['site_url'] = get_site_url();

		$this->name               = $name;
		$this->internal_name      = sanitize_key( strtolower( $name ) );
		$this->site_url           = $args['site_url'];
		$this->bbbolt_url         = add_query_arg( 'bbbolt', $args['site_url'] );
		$this->registering_plugin = $args['registering_plugin'];

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init', array( &$this, 'check_requirements' ), 11 );
		add_action( 'init', array( &$this, 'flush_rules' ), 12 );
		add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( &$this, 'request_handler' ), -1 );
		add_filter( 'status_header', array( &$this, 'unset_404' ), 10, 4 );
	}

	/**
	 * Check to make sure bbPress is activate as the server relies on it.
	 **/
	function check_requirements(){

		if( ! defined( 'BBP_VERSION' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );	// Need deactivate_plugins()
			deactivate_plugins( $this->registering_plugin );
			wp_die( sprintf( 'The %sbbPress plugin%s must be active for %sbbBolt%s to work its magic. bbBolt has been deactivated. %sInstall & activate bbPress%s', 
							 '<a href="http://wordpress.org/extend/plugins/bbpress/">', '</a>', 
							 '<a href="http://bbbolt.org/">', '</a>', 
							 '<br/><a href="'.admin_url('plugins.php').'">', '&nbsp;&raquo;</a>' 
					)
			);
		}
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
		if( ! isset( $wp_query->query_vars['bbbolt'] ) )
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
	 * to tell the end user that if the request is for bbbolt, it is not a 404.
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
	function add_rewrite_rules( $wp_rewrite ) {
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


