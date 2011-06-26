<?php

/**
 * The bbBolt Server Work Engine
 **/
class bbBolt_Server {

	private $name;
	private $bbbolt_url;
	private $bbbolt_client;
	private $subscription;
	private $registering_plugin;

	public $labels;

	/**
	 * Create the bbBolt Server Object according to intersection between default settings & parameters in $args array.
	 * 
	 * @param $args['name'] default blog name, 
	 * @param $args['site_url'], default current site's URL,
	 * @param $args['registering_plugin'], default the file and folder of the plugin with calls register_bbbolt_server
	 * @param $args['subscription']: default 20 USD/month until subscription is cancelled. An array of name => value pairs relating to the subscription.
	 * 		payment_amount, default 20, the amount of the subscription. Regardless of the specified currency, the format must have decimal point. The decimal point must include exactly 2 digits to the right and an optional thousands separator to the left, which must be a comma. For example, specify EUR 2.000,00 as 2000.00 or 2,000.00. The specified amount cannot exceed USD $10,000.00, regardless of the currency used.
	 * 		billing_period, default 'Month', the unit to calculate the billing cycle. One of Day, Week, Month, Year.
	 * 		billing_frequency, default 12, The number of billing periods that make up the billing cycle. Combined with billing_period, must be less than or equal to one year.
	 * 		billing_total_cycles, default 0, The total number of billing cycles. If you do not specify a value, the payments continue until PayPal (or the buyer) cancels or suspends the profile. A value other than the default of 0 terminates the payments after the specified number of billing cycles. For example billing_total_cycles = 2 with billing_frequency = 12 and billing_period = Month would continue the payments for two years. 
	 * 		currency_code, default 'USD'
	 * 		initial_amount, default 0, An optional non-recurring payment made when the recurring payments profile is created.
	 * 
	 **/
	function __construct( $name, $args = array() ){

		$defaults = array(
			'name'     => get_bloginfo( 'name' ),
			'site_url' => get_site_url(),
			'labels'   => array( 'name' => ucfirst( $name ) ),
			'paypal'   => array( // Global details for PayPal
				'sandbox'      => true,
				'currency'     => 'USD',
				'cancel_url'   => add_query_arg( array( 'bbbolt'=> 1, 'return' => 'cancel' ), site_url() ),
				'return_url'   => add_query_arg( array( 'bbbolt'=> 1, 'return' => 'paid' ), site_url() )
			),
			'subscription' => array(
				'start_date'         => date( 'Y-m-d\TH:i:s', time() + ( 24 * 60 * 60 ) ),
				'description'        => get_bloginfo( 'name' ) . __( ' Support Subscription', 'bbbolt' ),
				// Price of the Subscription
				'amount'             => '20.00',
				'initial_amount'     => '0.00',
				'average_amount'     => '25',
				// Temporal Details of the Subscription
				'period'             => 'Month',
				'frequency'          => '1',
				'total_cycles'       => '0',
				// Trial Period details
				'trial_amount'       => '0.00',
				'trial_period'       => 'Month',
				'trial_frequency'    => '0',
				'trial_total_cycles' => '0'
			)
		);

		$args = wp_parse_args( $args, $defaults );

		if( empty( $args['registering_plugin'] ) ){ // Get the handle fo the calling plugin
			$backtrace = debug_backtrace();
			$args['registering_plugin'] = basename( dirname( $backtrace[1]['file'] ) ) . '/' . basename( $backtrace[0]['file'] );
		}

		$this->name               = sanitize_key( $name );
		$this->site_url           = $args['site_url'];
		$this->labels             = (object)$args['labels'];
		$this->paypal             = (object)$args['paypal'];
		$this->subscription       = (object)$args['subscription'];
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
			wp_die( sprintf( __( 'The %sbbPress plugin%s must be active for %sbbBolt%s to work its magic. bbBolt has been deactivated. %sInstall & activate bbPress%s', 'bbbolt' ), 
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
			$errors = register_new_user( $user_login, $user_email );
			if ( ! is_wp_error( $errors ) ) {
				$redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : 'wp-login.php?checkemail=registered';
				wp_safe_redirect( $redirect_to );
				exit();
			}
		}
		$redirect_to = apply_filters( 'registration_redirect', ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '' );
		?>
		<div id="register-container">
		<?php if( ! get_option( 'users_can_register' ) ) : ?>
			<h3><?php printf( __( 'Registrations for %s are Closed', 'bbbolt' ), $this->labels->name ); ?></h3>
			<p><?php printf( __( 'Please contact the %s developers to request they open registration on %s.', 'bbbolt' ), $this->labels->name, '<a href="'.$this->site_url.'">'.$this->site_url.'</a>' ); ?></p>
		<?php elseif( isset( $_GET['token'] ) ) : // Subscriber returned from PayPal Payment ?>
			<?php if( isset( $_GET['return'] ) && $_GET['return'] == 'paid' ) : ?>
				<?php // DoExpressCheckoutPayment & maybe GetExpressCheckoutDetails ?>
				<?php // useraction=continue supress thank you page ?>
			<?php elseif( isset( $_GET['return'] ) && $_GET['return'] == 'cancel' ) : ?>
				<?php // GetExpressCheckoutDetails ?>
				<?php endif; ?>
		<?php else : // Output Login form ?>
			<h3><?php _e( 'Sign up for Lightning Fast Support', 'bbbolt' ); ?></h3>
			<p><?php printf( __( 'Supporting  %s gets you premium support and influence over future development.', 'bbbolt' ), $this->labels->name  ); ?></p>
			<p><?php printf( __( 'Subscriptions are %s per %year', 'bbbolt' ), $this->subscription->amount, $this->subscription->period ); ?></p>
			<form name="registerform" id="registerform" action="<?php echo site_url('wp-login.php?action=register', 'login_post') ?>" method="post">
				<p>
					<label><?php _e( 'Username:', 'bbbolt' ) ?>
					<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" tabindex="10" /></label>
				</p>
				<p>
					<label><?php _e( 'E-mail:', 'bbbolt' ) ?>
					<input type="text" name="user_email" id="user_email" class="input" value="<?php echo esc_attr(stripslashes($user_email)); ?>" size="25" tabindex="20" /></label>
				</p>
				<?php do_action( 'register_form' ); ?>
				<p id="reg_passmail"><?php _e( 'A password will be e-mailed to you.', 'bbbolt' ); ?></p>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
				<p class="submit">
					<input type="image" name="bbb-submit" id="bbb-submit" src="https://www.paypal.com/en_US/i/btn/btn_dg_pay_w_paypal.gif" />
				</p>
			</form>
		<?php endif; ?>
			<p id="already-member">
				<?php _e( 'Already have an account?', 'bbbolt' ); ?>&nbsp;<a id="login-link" href="<?php echo site_url('wp-login.php', 'login') ?>" title="<?php _e( 'Login', 'bbbolt' ) ?>"><?php _e( 'Login here.', 'bbbolt' ) ?></a>
			</p>
		</div>
		<?php $this->login_form(); ?>
		<script>
			jQuery(document).ready(function($){
				$('#login-link').click(function(){
					$('#login-container').slideDown();
					$('#register-container').slideUp();
					return false;
				})
			});
		</script>
	<?php
	}

	/**
	 * Outputs the HTML login form for the plugin's support page.
	 **/
	function login_form() { ?>
		<div id="login-container" style="display:none;">
			<h3><?php _e( 'Login', 'bbbolt' ); ?></h3>
			<p><?php printf( __( 'Login to the %s support system.', 'bbbolt' ), $this->labels->name ); ?></p>
			<?php wp_login_form( array( 'redirect' => site_url( $_SERVER['REQUEST_URI'] ) ) ); ?>
			<a id="forgot-link" href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
		</div>
		<div id="forgot-container" style="display:none;">
			<form name="lostpasswordform" id="lostpasswordform" action="<?php echo site_url('wp-login.php?action=lostpassword', 'login_post') ?>" method="post">
				<p>
					<label><?php _e('Username or E-mail:') ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" tabindex="10" /></label>
				</p>
			<?php do_action('lostpassword_form'); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Get New Password'); ?>" tabindex="100" /></p>
			</form>
		</div>
		<script>
			jQuery(document).ready(function($){
				$('#forgot-link').click(function(){
					$('#forgot-container').slideDown();
					$('#login-container').slideUp();
					return false;
				})
			});
		</script>
	<?php
	}

	function request_handler(){
		global $wp_query;

		// Don't touch non bbbolt queries
		if( ! isset( $wp_query->query_vars['bbbolt'] ) )
			return;

		$this->get_header();
		if( ! is_user_logged_in() ) {
			$this->register_form();
		} else { ?>
			<h3><?php _e( "Don't Panic", 'bbbolt' ); ?></h3>
			<?php require_once( dirname( __FILE__ ) . '/dont-panic.php' );
		}
		$this->get_footer();
		exit;
	}

	/* TEMPLATE FUNCTIONS */
	function print_styles() { ?>
		<style>
			#registerform label {
				width: 200px;
			}
			#registerform label input {
				width: 250px;
			}
		</style>
	<?php
	}

	function get_header() {
		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<title><?php bloginfo( 'name' ); ?></title>
		<?php $this->print_styles(); ?>
		<?php wp_print_scripts( 'jquery' ); ?>
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


