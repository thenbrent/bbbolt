<?php

if( ! class_exists( 'bbBolt_Server' ) ) :

require_once('paypal/paypal-digital-goods.class.php');

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
	 * @param $name, string required. Internal name for this Server
	 * @param $paypal_credentials, array required. Your PayPal API Username, Password & Signature in a name => value array.
	 * @param $args['site_url'], string default current site's URL,
	 * @param $args['labels'], array. Name and description of your support system. Default name => Site Title, description => Site Title Support System.
	 * @param $args['registering_plugin'], default the file and folder of the plugin with calls register_bbbolt_server
	 * @param $args['paypal']:
	 * 		'currency_code', default 'USD', any ISO-4217 Currency Code (http://en.wikipedia.org/wiki/ISO_4217) which is support by PayPal (http://www.paypalobjects.com/en_US/ebook/PP_NVPAPI_DeveloperGuide/Appx_fieldreference.html)
	 * 		'subscription'default 20 USD/month until subscription is cancelled. An array of name => value pairs relating to the subscription.
	 * 			amount, default 20, the amount of the subscription. Regardless of the specified currency, the format must have decimal point. The decimal point must include exactly 2 digits to the right and an optional thousands separator to the left, which must be a comma. For example, specify EUR 2.000,00 as 2000.00 or 2,000.00. The specified amount cannot exceed USD $10,000.00, regardless of the currency used.
	 * 			period, default 'Month', the unit to calculate the billing cycle. One of Day, Week, Month, Year.
	 * 			frequency, default 1, The number of billing periods that make up the billing cycle. Combined with period, must be less than or equal to one year.
	 * 			total_cycles, default 0, The total number of billing cycles. If you do not specify a value, the payments continue until PayPal (or the buyer) cancels or suspends the profile. A value other than the default of 0 terminates the payments after the specified number of billing cycles. For example, with 'total_cycles' = 2, 'frequency' = 12 and 'period' = 'Month' the payments would continue for two years.
	 * 			initial_amount, default 0, An optional non-recurring payment made when the recurring payments profile is created.
	 * 
	 **/
	function __construct( $name, $paypal_credentials, $args = array() ){

		if( empty(  $paypal_credentials['username'] ) || empty(  $paypal_credentials['password'] ) || empty(  $paypal_credentials['signature'] ) )
			wp_die( __( 'You must give bbBolt your PayPal API username, password and signature. ', 'bbbolt' ) );

		$defaults = array(
			'site_url' => site_url(),
			'labels'   => array( 'name' => get_bloginfo('name'), 'description' => get_bloginfo('name') . __( ' Support Subscription', 'bbbolt' ) ),
			'paypal'   => array( // Global details for PayPal
				'sandbox'      => true,
				'currency'     => 'USD',
				'cancel_url'   => add_query_arg( array( 'bbbolt'=> 'paypal', 'return' => 'cancel' ), site_url( '/' ) ),
				'return_url'   => add_query_arg( array( 'bbbolt'=> 'paypal', 'return' => 'paid' ), site_url( '/' ) ),
				'subscription' => array(
					'start_date'         => date( 'Y-m-d\TH:i:s', time() + ( 24 * 60 * 60 ) ),
					'description'        => get_bloginfo( 'name' ) . __( ' Support Subscription', 'bbbolt' ),
					// Price of the Subscription
					'amount'             => '6.00',
					'initial_amount'     => '19.00',
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
			)
		);

		// Fill out defaults (can't use array_merge_recursive because it merges arrays have the same string keys into a new array)
		if( isset( $args['labels'] ) && is_array( $args['labels'] ) )
			$args['labels'] = array_merge( $defaults['labels'], $args['labels'] );
		else
			$args['labels'] = $defaults['labels'];

		if( isset( $args['paypal']['subscription'] ) && is_array( $args['paypal']['subscription'] ) )
			$args['paypal']['subscription'] = array_merge( $defaults['paypal']['subscription'], $args['paypal']['subscription'] );
		else
			$args['paypal']['subscription'] = $defaults['paypal']['subscription'];

		if( isset( $args['paypal'] ) && is_array( $args['paypal'] ) )
			$args['paypal'] = array_merge( $defaults['paypal'], $args['paypal'] );
		else
			$args['paypal'] = $defaults['paypal'];

		$args =  array_merge( $defaults, $args );

		if( empty( $args['registering_plugin'] ) ){ // Get the handle for the calling plugin
			$backtrace = debug_backtrace();
			$args['registering_plugin'] = basename( dirname( $backtrace[1]['file'] ) ) . '/' . basename( $backtrace[1]['file'] );
		}

		$this->name               = sanitize_key( $name );
		$this->site_url           = $args['site_url'];
		$this->labels             = (object)$args['labels'];
		$this->bbbolt_url         = add_query_arg( array( 'bbbolt' => 1 ), $args['site_url'].'/' );
		$this->registering_plugin = $args['registering_plugin'];

		$this->currency           = $args['paypal']['currency'];
		$this->subscription       = (object)$args['paypal']['subscription'];

		$this->paypal             = new PayPal_Digital_Goods( $paypal_credentials, $args['paypal'] );

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init',                   array( &$this, 'check_requirements' ), 11 );
		add_action( 'init',                   array( &$this, 'flush_rules' ), 12 );
		add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect',      array( &$this, 'request_handler' ), -1 );
		add_filter( 'status_header',          array( &$this, 'unset_404' ), 10, 4 );

		// If bbBolt is installed, site admins must want users to be able to register
		add_filter( 'option_users_can_register', array( &$this, 'users_can_register_override' ), 100 );

		// Front-end elements
		add_shortcode( 'bbbolt_registration_page',    array( &$this, 'shortcode_registration_page_handler' ) );
		add_shortcode( 'bbbolt_registration_form',    array( &$this, 'shortcode_registration_form_handler' ) );
		add_shortcode( 'bbbolt_signup_complete',      array( &$this, 'shortcode_signup_complete_handler' ) );
		add_shortcode( 'bbbolt_signup_cancelled',     array( &$this, 'shortcode_signup_cancelled_handler' ) );
		add_shortcode( 'bbbolt_logged_in',            array( &$this, 'shortcode_logged_in_handler' ) );
		add_shortcode( 'bbbolt_subscription_details', array( &$this, 'shortcode_subscription_details_handler' ) );

		// Maybe enqueue necessary scripts for front end
		add_action( 'wp_print_styles', array( &$this, 'maybe_print_styles_scripts' ), 1 );

		// Remove X-Frame setting that restricts logins
		if( isset( $_GET['bbbolt'] ) ) {
			remove_action( 'login_init', 'send_frame_options_header' );
			remove_action( 'admin_init', 'send_frame_options_header' );
		}
	}


	/**
	 * Routes requests and chooses which view to display
	 */
	function request_handler(){
		global $wp_query, $bbb_message;

		// Don't touch non bbbolt queries
		if( ! isset( $wp_query->query_vars['bbbolt'] ) )
			return;

		if( $wp_query->query_vars['bbbolt'] == 'register-user' )
			$this->register_user();

		// Routing logic, doesn't work in a switch as nicely as one might think
		if( isset( $_POST['bbb_topic_submit'] ) ) {

			// No Simple save function for bbPress, bbp_new_topic_handler does the save but also does a redirect, so we need to force it to redirect back to us.
			$bbb_message = __( 'Thanks for your submission. We will reply soon.', 'bbbolt' );
			add_filter( 'bbp_new_topic_redirect_to', array( &$this, 'get_url' ) );
			bbp_new_topic_handler();

			// If the bbp_new_topic_handler() function didn't do a redirect and exit() there was an error so print the support form with the error message
			unset( $GLOBALS['bbb_message'] );

			$this->get_header();
			$this->support_form();
			$this->get_footer();
			exit();

		} elseif( $wp_query->query_vars['bbbolt'] == 'paypal' ) {

			$this->paypal_return();
			exit();

		} elseif( $wp_query->query_vars['bbbolt'] == 'checkout' ) {

			if( ! wp_verify_nonce( $_POST['bbb-nonce'], __FILE__ ) ) 
				die( 'Nonce Security Check Failed. Please try again or contact the site administrator.' );

			// Redirect the current user back to the same page they registered on
			if( ! empty( $_POST['redirect_to'] ) ) {
				$redirect_to = array( 'redirect_to' => urlencode( $_POST['redirect_to'] ) );
				$this->paypal->cancel_url = add_query_arg( $redirect_to, $this->paypal->cancel_url );
				$this->paypal->return_url = add_query_arg( $redirect_to, $this->paypal->return_url );
			}

			// Store the users credentials for 30 minutes (also requests a Token from PayPal).
			set_transient( $this->paypal->token(), array( 'email' => $_POST['bbb-email'], 'password' => $this->encrypt( $_POST['bbb-password'] ) ), 60 * 30 );

			header( 'Location: ' . $this->paypal->get_checkout_url() );
			exit();

		} elseif( ! $this->contains_shortcode() ) { // We're in the bbBolt Slider

			$this->get_header();

			// Don't go changing this order, it's important
			if( $wp_query->query_vars['bbbolt'] == 'payment-cancelled' ) { 

				echo $this->payment_cancelled();

			} elseif( ! is_user_logged_in() ) {

				$this->login_signup_process();

			} elseif( $wp_query->query_vars['bbbolt'] == 'inbox' ) {

				$this->support_inbox();

			} else { // Default to new topic form

				$this->support_form();

			}

			$this->get_footer();

			exit();
		}
	}


	/**
	 * Takes care of the signup user flow.
	 */
	function login_signup_process(){ 
		global $wp_query, $bbb_message;
		?>
		<div id="register-container">
			<ol id="register-progress">
				<li id="register-step"<?php if( ! isset( $_GET['return'] ) ) echo 'class="current"'; ?>>1. Registration</li>
				<li id="payment-step"<?php if( isset( $_GET['return'] ) ) echo 'class="current"'; ?>>2. Payment</li>
				<li id="post-step">3. Post a Question</li>
			</ol>

			<p><?php printf( __( 'Sign-up to a support subscription with %s to get exclusive access to support and influence over the future of %s.', 'bbbolt' ), $this->labels->name, $this->labels->name ); ?></p>
			<p><?php printf( __( 'To sign-up, enter your account details below. You will then be redirected to PayPal to authorise this subscription.', 'bbbolt' ) ); ?></p>
			<p><?php printf( __( 'Subscription: %s', 'bbbolt' ), $this->get_subscription_details() ); ?></p>
			<?php $this->registration_form(); ?>

			<p id="already-member">
				<?php _e( 'Already have an account?', 'bbbolt' ); ?>&nbsp;<a id="login-link" href="<?php echo site_url('wp-login.php', 'login') ?>" title="<?php _e( 'Login', 'bbbolt' ) ?>"><?php _e( 'Login here.', 'bbbolt' ) ?></a>
			</p>
		</div>

		<?php $this->login_form(); ?>
		<?php
	}


	/**
	 * Register a new user with the site. Log them in and redirect them to the support form (with a message notifying them of successful subscription)
	 */
	function register_user(){
		global $bbb_message, $wp_query;

		// Create the Recurring Payment Profile with PayPal
		$response = $this->paypal->start_subscription();

		$user_credentials = get_transient( $this->paypal->token() );

		$user_credentials['password'] = $this->decrypt( $user_credentials['password'] );

		delete_transient( $this->paypal->token() );

		// Create User
		$user_id = wp_create_user( $user_credentials['email'], $user_credentials['password'], $user_credentials['email'] );

		// Make sure the user was created successfully
		if( is_wp_error( $user_id ) ) {
			$bbb_message = $user_id->get_error_message();
			$this->get_header();
			unset( $user_credentials['password'] );
			$this->registration_form( $user_credentials );
			$this->get_footer();
			exit;
		}

		// Log the new user in
		wp_set_current_user( $user_id );
		$user = wp_signon( array( 'user_login' => $user_credentials['email'], 'user_password' => $user_credentials['password'], 'rememberme' => true ) );

		// Store the user's Payment Profile ID 
		update_user_meta( $user_id, 'paypal_payment_profile_id', urldecode( $response['PROFILEID'] ) );

		$bbb_message = sprintf( __( 'Thanks for signing up. Your account has been created with the username %s.', 'bbbolt' ), $user_credentials['email'] );

		return $user_id;
	}


	/**
	 * 
	 */
	function payment_cancelled( $redirect_to = '' ){

		if( empty( $redirect_to ) )
		 	$redirect_to = $this->get_url();

		$cancelled = '<h3>' . __( 'Sign-Up Cancelled', 'bbbolt' ) . '</h3>'
				   . '<p>' . __( 'You have successfully terminated the subscription process.', 'bbbolt' ) . '</p>'
				   . '<p><a href="' . $redirect_to . '">' . __( 'Sign-up again', 'bbbolt' ) . ' &raquo;</a></p>';

		return apply_filters( 'bbbolt_payment_cancelled', $cancelled );
	}


	/* TEMPLATE FUNCTIONS */


	/**
	 * Builds the HTML registration form for the plugin's support page.
	 **/
	function get_registration_form( $credentials = array(), $redirect_to = '' ) {

		if( is_user_logged_in() )
			return sprintf( __( '%sYou are already registered and logged in.%s', 'bbbolt' ), '<p class="message">', '</p>' );

		$credentials['email']    = ( isset( $credentials['email'] ) ) ? esc_attr( stripslashes( $credentials['email'] ) ) : '';

		$form  = '<form name="bbb-registerform" id="bbb-registerform" action="" method="post">';

		$form .= '<p><label>' . __( 'E-mail', 'bbbolt' ) . '</label>';
		$form .= '<input type="text" name="bbb-email" id="bbb-email" class="input" size="25" tabindex="20" value="' . $credentials['email'] . '"/></p>';

		$form .= '<p><label>' . __( 'Password', 'bbbolt' ) . '</label>';
		$form .= '<input type="password" name="bbb-password" id="bbb-password" class="input" value="" size="25" tabindex="30" /></p>';

		$form .= '<p class="submit">';
		$form .= $this->paypal->get_buy_button( array( 'type' => 'input', 'get_token' => false, '' ) ) . '</p>';
		$form .= $this->paypal->get_script();
		//$form .= '<input type="hidden" id="bbb-paypal-token" name="bbb-paypal-token" value="' . $this->paypal->token() . '">';

		$form .= '<input type="hidden" name="bbbolt" value="checkout" />';
		$form .= '<input type="hidden" name="redirect_to" value="' . $redirect_to . '" />';
		$form .= wp_nonce_field( __FILE__, 'bbb-nonce', true, false );

		$form .= '</form>';

		return apply_filters( 'bbbolt-registration-form', $form, $credentials );
	}


	/**
	 * Outputs the HTML registration form for the plugin's support page @uses get_registration_form().
	 **/
	function registration_form( $credentials = array() ){
		echo $this->get_registration_form( $credentials );
	}


	/**
	 * Outputs the HTML login form for the plugin's support page.
	 **/
	function login_form() { ?>
		<div id="login-container" style="display:none;">
			<h3><?php _e( 'Login', 'bbbolt' ); ?></h3>
			<p><?php printf( __( 'Login to the %s support system.', 'bbbolt' ), $this->labels->name ); ?></p>
			<?php wp_login_form( array( 'redirect' => esc_url( $_SERVER['REQUEST_URI'] ) ) ); ?>
			<a id="forgot-link" href="<?php echo $this->get_url(); ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
		</div>
		<div id="forgot-container" style="display:none;">
			<form name="lostpasswordform" id="lostpasswordform" action="" method="post">
				<p>
					<label><?php _e('Username or E-mail:') ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="10" />
				</p>
			<?php do_action('lostpassword_form'); ?>
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Get New Password'); ?>" tabindex="100" /></p>
			</form>
		</div>
	<?php
	}


	/**
	 * Returns a string describing the subscription. eg. $20 per month for 3 years. 
	 * 
	 * Applies filters to the PayPal object's subscription string.
	 */
	function get_subscription_details(){

		$subscription_details = apply_filters( 'bbbolt_subscription_details', $this->paypal->get_subscription_string(), &$this );

		return $subscription_details;
	}


	/**
	 * Prints the a string describing the subscription as created by @see get_subscription_details().
	 */
	function subscription_details(){

		echo $this->get_subscription_details();
	}


	/**
	 * Close the PayPal iframe and redirec to the appropriate template when a user returns from PayPal
	 */
	function paypal_return() {

		$paid_params      = array( 'bbbolt' => 'register-user', 'token' => $_GET['token'] );
		$cancelled_params = array( 'bbbolt' => 'payment-cancelled' );

		if( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
			$url = ( $_GET['return'] == 'paid' ) ? add_query_arg( $paid_params, $redirect_to ) : add_query_arg( $cancelled_params, $redirect_to );
		} else {
			$url = ( $_GET['return'] == 'paid' ) ? $this->get_url( $paid_params ) : $this->get_url( $cancelled_params );
		}
		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<title><?php bloginfo( 'name' ); ?></title>
			<?php // Short circuit logic to account for when script is loaded in bbBolt iframe & from front-end ?>
			<script>if(parent!=window.top||self!=top){parent.location.href = "<?php echo $url ?>";}</script>
		</head>
		<body>
			<img id="bbb-form-loading" src="<?php echo $this->get_dir_url(); ?>/images/loader.gif">
		</body>
		</html>
		<?php
	}


	/**
	 * Display the support form
	 */
	function support_form() { ?>

		<h3><?php _e( 'New Ticket', 'bbbolt' ); ?></h3>

		<?php if( $this->get_messages() ) : ?>
			<div id="message" class="updated fade"><p><?php echo $this->get_messages(); ?></p></div>
		<?php endif; ?>

		<?php if ( ( bbp_is_topic_edit() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) || current_user_can( 'publish_topics' ) || ( bbp_allow_anonymous() && !is_user_logged_in() ) ) : ?>

			<?php if ( ( !bbp_is_forum_category() && ( !bbp_is_forum_closed() || current_user_can( 'edit_forum', bbp_get_topic_forum_id() ) ) ) || bbp_is_topic_edit() ) : ?>

				<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form">

					<form id="new-post" name="new-post" method="post" action="">

							<?php do_action( 'bbp_template_notices' ); ?>

							<div>
								<?php if ( !bbp_is_forum() ) : ?>
									<p>
										<label for="bbp_forum_id"><?php _e( 'Forum:', 'bbbolt' ); ?></label><br />
										<?php bbp_dropdown( array( 'selected' => bbp_get_form_topic_forum() ) ); ?>
									</p>
								<?php endif; ?>

								<?php bbp_get_template_part( 'bbpress/form', 'anonymous' ); ?>

								<p>
									<label for="bbp_topic_title"><?php _e( 'Subject:', 'bbbolt' ); ?></label><br />
									<input type="text" id="bbp_topic_title" value="<?php bbp_form_topic_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_title" />
								</p>

								<p>
									<label for="bbp_topic_content"><?php _e( 'Message:', 'bbbolt' ); ?></label><br />
									<textarea id="bbp_topic_content" tabindex="<?php bbp_tab_index(); ?>" name="bbp_topic_content" cols="51" rows="6"><?php bbp_form_topic_content(); ?></textarea>
								</p>

								<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>

									<div class="bbp-template-notice">
										<p><?php _e( 'You can post unrestricted HTML content.', 'bbbolt' ); ?></p>
									</div>

								<?php endif; ?>

								<?php if ( ! bbp_is_topic_edit() ) : ?>
									<p>
										<label for="bbp_topic_tags"><?php _e( 'Tags:', 'bbbolt' ); ?></label><br />
										<input type="text" value="<?php bbp_form_topic_tags(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_tags" id="bbp_topic_tags" />
									</p>
								<?php endif; ?>

								<?php if ( bbp_is_subscriptions_active() && ! bbp_is_anonymous() && ( !bbp_is_topic_edit() || ( bbp_is_topic_edit() && !bbp_is_topic_anonymous() ) ) ) : ?>
									<p>
										<input name="bbp_topic_subscription" id="bbp_topic_subscription" type="checkbox" value="bbp_subscribe" <?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />
										<?php if ( bbp_is_topic_edit() && ( $post->post_author != bbp_get_current_user_id() ) ) : ?>
											<label for="bbp_topic_subscription"><?php _e( 'Notify the author of replies via email', 'bbbolt' ); ?></label>
										<?php else : ?>
											<label for="bbp_topic_subscription"><?php _e( 'Notify me of replies via email', 'bbbolt' ); ?></label>
										<?php endif; ?>
									</p>
								<?php endif; ?>

								<?php if ( bbp_is_topic_edit() ) : ?>
									<fieldset>
										<legend><?php _e( 'Revision', 'bbbolt' ); ?></legend>
										<div>
											<input name="bbp_log_topic_edit" id="bbp_log_topic_edit" type="checkbox" value="1" <?php bbp_form_topic_log_edit(); ?> tabindex="<?php bbp_tab_index(); ?>" />
											<label for="bbp_log_topic_edit"><?php _e( 'Keep a log of this edit:', 'bbbolt' ); ?></label><br />
										</div>

										<div>
											<label for="bbp_topic_edit_reason"><?php printf( __( 'Optional reason for editing:', 'bbbolt' ), bbp_get_current_user_name() ); ?></label><br />
											<input type="text" value="<?php bbp_form_topic_edit_reason(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_edit_reason" id="bbp_topic_edit_reason" />
										</div>
									</fieldset>
								<?php endif; ?>

								<div class="bbp-submit-wrapper">
									<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbb_topic_submit" name="bbb_topic_submit" class="button-secondary"><?php _e( 'Submit', 'bbbolt' ); ?></button>
								</div>
							</div>

							<?php bbp_topic_form_fields(); ?>

					</form>
				</div>

			<?php elseif ( bbp_is_forum_closed() ) : ?>

				<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
					<h2 class="entry-title"><?php _e( 'Sorry!', 'bbbolt' ); ?></h2>
					<div class="bbp-template-notice">
						<p><?php _e( 'This forum is closed to new topics.', 'bbbolt' ); ?></p>
					</div>
				</div>

			<?php endif; ?>

		<?php else : ?>

			<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
				<h2 class="entry-title"><?php _e( 'Sorry!', 'bbbolt' ); ?></h2>
				<div class="bbp-template-notice">
					<p><?php is_user_logged_in() ? _e( 'You cannot create new topics at this time.', 'bbbolt' ) : _e( 'You must be logged in to create new topics.', 'bbbolt' ); ?></p>
				</div>
			</div>

		<?php endif;
	}


	/**
	 * Display the support inbox for the user.
	 */
	function support_inbox(){
		global $current_user;

		$current_user = wp_get_current_user();
			?>
			<div class="wrap">
				<table class="widefat">
					<thead>
						<tr>
							<th id="bbb_subject"><?php _e( 'Subject', 'bbbolt' ); ?></th>
							<th id="bbb_message"><?php _e( 'Message', 'bbbolt' ); ?></th>
							<th id="bbb_author"><?php _e( 'Author', 'bbbolt' ); ?></th>
							<th id="bbb_freshness"><?php _e( 'Freshness', 'bbbolt' ); ?></th>
							<th id="bbb_reply"><?php _e( 'Reply', 'bbbolt' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<th id="bbb_subject_foot"><?php _e( 'Subject', 'bbbolt' ); ?></th>
						<th id="bbb_message_foot"><?php _e( 'Message', 'bbbolt' ); ?></th>
						<th id="bbb_author_foot"><?php _e( 'Author', 'bbbolt' ); ?></th>
						<th id="bbb_freshness_foot"><?php _e( 'Freshness', 'bbbolt' ); ?></th>
						<th id="bbb_reply_foot"><?php _e( 'Reply', 'bbbolt' ); ?></th>
					</tfoot>
					<tbody>
					<?php
					$topics = new WP_Query( array( 'post_type' => array( 'topic' ), 'author' => $current_user->ID, 'post_status' => 'publish' ) );
					if( $topics->have_posts() ) : 
						while( $topics->have_posts() ) : $topics->the_post();
							// Output the topic
							$topic_reply_url = get_permalink() . '#new-post';
							echo '<tr>'.
									'<td>' . get_the_title() . '</td>'.
									'<td>' . get_the_content() . '</td>'.
									'<td>' . ( ( get_the_author() == $current_user->display_name ) ? 'You' : get_the_author() ) . '</td>'.
									'<td>' . sprintf( __( '%s ago', 'bbbolt' ), human_time_diff( strtotime( get_the_date() ), current_time( 'timestamp' ) ) ) . '</td>'.
									'<td><a href="' . $topic_reply_url . '" target="_blank">' . __( 'Reply', 'bbbolt' ) . '&nbsp;&raquo;</a></td>'.
								'</tr>';
							// Check if there are replies to this topic
							$replies = new WP_Query( array( 'post_type' => array( 'reply' ), 'post_parent' => get_the_ID(), 'post_status' => 'publish' ) );
							if( $replies->have_posts() ) : 
								while( $replies->have_posts() ) : $replies->the_post();
									// Output the Reply
									echo '<tr>'.
											'<td class="reply-title">&#8212;&nbsp;' . __( 'Reply:', 'bbbolt' ) . '</td>'.
											'<td>' . get_the_content() . '</td>'.
											'<td>' . ( ( get_the_author() == $current_user->display_name ) ? 'You' : get_the_author() ) . '</td>'.
											'<td>' . sprintf( __( '%s ago', 'bbbolt' ), human_time_diff( strtotime( get_the_date() ), current_time( 'timestamp' ) ) ) . '</td>'.
											'<td><a href="' . $topic_reply_url . '" target="_blank">' . __( 'Reply', 'bbbolt' ) . '&nbsp;&raquo;</a></td>'.
										'</tr>';
								endwhile;
							else :
								echo '<tr><td class="no-replies" colspan="5">' . __( 'No replies yet.', 'bbbolt' ) . '</td></tr>';
							endif;
						endwhile;
					else :
						echo '<tr><td class="no-topics" colspan="5">' . __( 'You have not yet submitted any topics.', 'bbbolt' ) . '</td></tr>';
					endif;
					?>
				</tbody>
			</table>
		</div>
		<?php
	}


	/**
	 * Returns any messages from other functions in the class. Useful for printing messages in templates.
	 */
	function get_messages(){
		global $bbb_message;

		$message = '';

		if( isset( $bbb_message ) )
			$message .= $bbb_message . '<br/>';

		if( isset( $_GET['bbb-msg'] ) )
			$message .= $_GET['bbb-msg'];

		return $message;
	}


	/**
	 * Output custom CSS for the bbBolt iframe.
	 */
	function print_styles() { ?>
		<style>
			html,body {
				background-color: transparent;
				height: auto;
			}
			body {
				padding: 0 10px;
			}
			/* Registration Form */
			#bbb-registerform label,
			#login-container label {
				display: inline-block;
				width: 100px;
			}
			#bbb-registerform input[type="text"],
			#bbb-registerform input[type="password"],
			#login-container input[type="text"],
			#login-container input[type="password"] {
				display: inline-block;
				width: 200px;
				margin: 0px 5px;
				padding: 2px 5px;
			}
			#bbb-registerform .strengthy-show-toggle {
				margin: 10px 0px 5px 105px;
			}
			#strengthy-show-toggle-bbb-password {
				margin: 0px 5px 0px 2px;
			}
			#bbb-registerform .strengthy-error {
				background-color: lightYellow;
				border: 1px solid #E6DB55;
				padding: 0.2em 0.5em;
			}
			#bbb-registerform .strengthy-valid {
				background: #ECF4E8;
				border: 1px solid #2C8A00;
				padding: 0.2em 0.5em;
			}
			#bbb-registerform .submit {
				border-color: transparent;
			}
			#bbb-registerform .submit input {
				border: none transparent;
				background: transparent none;
			}
			#bbb-registration {
				padding: 5px 10px;
			}
			#login-container .login-remember label {
				width: 250px;
			}
			.bbp-topic-form input[type="text"],
			.bbp-topic-form textarea {
				width: 330px;
			}
			.bbp-topic-form legend {
				float: left;
				margin: 0px 0px 12px;
				width: 100%;
			}
			.message {
				background-color: lightYellow;
				border: 1px solid #E6DB55;
				padding: 0.4em 1em;
			}
			/* Registration Progress Tracker */
			#register-progress {
				margin-left: 0;
			}
			#register-progress li{
				background-color: #F5F5F5;
				display: inline-block;
				font-size: 12px;
				position: relative;
				padding: 0.5em 1em 0.4em;
				border: 1px solid #CCC;
				color: #AFAFAF;
				-webkit-border-radius:0.2em;
				-moz-border-radius:0.2em;
				border-radius:0.2em;
				margin-right:1.2em;
				text-align: center;
				width: auto;
			}
			#welcome-step:before, #payment-step:before, 
			#register-step:before {
				content: "";
				position: absolute;
				border-style: solid;
				display: block;
				width: 0;
				top: -1px;
				bottom: auto;
				left: auto;
				right: -14px;
				border-width: 14px 0 14px 14px;
				border-color: transparent #CCC;
			}
			#welcome-step:after, #payment-step:after, 
			#register-step:after {
				content:"";
				position: absolute;
				border-style: solid;
				display: block; 
				width: 0;
				top: 0px;
				bottom: auto;
				left: auto;
				right: -13px;
				border-width: 13px 0 13px 13px;
				border-color: transparent #F5F5F5;
			}
			#register-progress .current {
				background-color: lightYellow;
				border-color: #E6DB55;
				color: #333;
			}
			#register-progress .current:before {
				border-color: transparent #E6DB55;
			}
			#register-progress .current:after {
				border-color: transparent lightYellow;
			}
			/* Support Inbox */
			.widefat td.no-topics {
				padding-left: 1em;
			}
			.widefat td.no-replies,
			.widefat td.reply-title {
				padding-left: 3em;
			}
		</style>
	<?php
	}


	/**
	 * Return the scripts used to enhance different parts of the login/registration/form submission process.
	 */
	function get_scripts() { 
		$ajax_url        = admin_url( 'admin-ajax.php' );
		$loading_gif_url = $this->get_dir_url() . '/images/loader.gif';

		$script = <<<SCRIPT
<script>
	jQuery(document).ready(function($){
		// Show Login Form
		$('#login-link').click(function(){
			$('#login-container').slideDown();
			$('#register-container').slideUp();
			return false;
		});

		// Show forgot password form
		$('#forgot-link').click(function(){
			$('#forgot-container').slideDown();
			$('#login-container').slideUp();
			return false;
		})

		// When PayPal pops up - dim the Registration frame, change progress meter & store credentials
		$('#paypal-submit').click(function(){
			var valid = validate_form();
			if( valid.status === false ) {
				if($('.message').length)
					$('.message').fadeOut('fast', function(){
						$(this).text(valid.message).fadeIn();
					});
				else 
					$('<div class="message">'+valid.message+'</div>').hide().prependTo('#bbb-registerform').slideDown();
				$('#PPDGFrame').remove();
				return false;
			} else {
				$('#register-progress li').removeClass('current');
				$('#payment-step').addClass('current');
				$('#bbb-registerform p.submit input').fadeOut(200, function(){
					$('<img class="loading" src="$loading_gif_url" />').hide().prependTo('#bbb-registerform p.submit').fadeIn();
				});
				function hideLoader() {
					if( dg.isOpen() ) {
						$('#bbb-registerform .submit').fadeOut();
						abortTimer();
					} else {
						tid = setTimeout(hideLoader, 5000);
					}
				}
				var tid = setTimeout(hideLoader, 5000);
				function abortTimer() { // to be called when you want to stop the timer
				  clearTimeout(tid);
				}
				return true;
			}
		});

		// Attach password strength meter to the registration form
		$('#bbb-password').strengthy({ minLength: 5, msgs: [ 'Weak', 'Weak', 'Good', 'Good', 'Great!', 'Show password' ] });

		function validate_form(){
			var msg, status;
			status = false;
			if($('#bbb-email').val().length == 0 )
				msg = 'You must enter an email address.';
			else if($('#bbb-password').val().length == 0 )
				msg = 'You must enter a password.';
			else
				status = true;
			return { 'status': status, 'message': msg}
		}
	});
</script>
SCRIPT;

		return apply_filters( 'bbbolt_server_scripts', $script );
	}


	/**
	 * Print the javascript used to enhance different parts of the login/registration/form submission process.
	 */
	function print_scripts() { 
		echo $this->get_scripts();
	}


	/**
	 * Get all required header elements for the bbBolt iframe and output them
	 */
	function get_header() {
		wp_enqueue_script( 'strengthy', $this->get_dir_url().'/js/jquery.plugins.js', array( 'jquery' ) );
		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<title><?php bloginfo( 'name' ); ?></title>
		<?php 
			wp_admin_css( 'global', true );
			wp_admin_css( 'wp-admin', true );
			wp_admin_css( 'colors-fresh', true );
			wp_admin_css( 'ie', true );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'strengthy' );
			$this->print_styles(); 
		?>
		</head>
		<body <?php body_class('bbbolt'); ?>>
			<h2 class="bbbolt-title"><?php echo $this->labels->name; ?></h2>
	<?php
	}


	/**
	 * Get all required footer elements for the bbBolt iframe and output them
	 */
	function get_footer() { 
		$this->print_scripts();
		?>
		</body>
		</html>
		<?php
	}


	/**
	 * Checks if a post contains the registration form shortcode and prints appropriate styles & scripts if it does. 
	 */
	function maybe_print_styles_scripts() {
		if( $this->contains_shortcode() ) {
			$this->print_styles();
			wp_enqueue_script( 'bbbolt-plugins', $this->get_dir_url() . '/js/jquery.plugins.js', array( 'jquery' ) );
		}
	}


	/* SHORTCODE FUNCTIONS */


	/**
	 * Mostly a wrapper for the shortcodes below.
	 */
	function shortcode_registration_page_handler( $attributes, $content = '' ) {
		global $wp_query; 

		extract( shortcode_atts( array(
			'id' => 'bbbolt-registration-page',
			'class' => '',
		), $attributes ) );

		$display = "<div id='$id' class='$class'>";

		$display .= do_shortcode( $content );

		// Make sure each step of the sign-up process is accounted for
		if( ! $this->contains_shortcode( 'bbbolt_subscription_details', $content ) ) 
			$display .= do_shortcode( '[bbbolt_subscription_details]' );

		if( ! $this->contains_shortcode( 'bbbolt_registration_form', $content ) ) 
			$display .= do_shortcode( '[bbbolt_registration_form]' );

		if( ! $this->contains_shortcode( 'bbbolt_signup_complete', $content ) )
			$display .= do_shortcode( '[bbbolt_signup_complete]' );

		if( ! $this->contains_shortcode( 'bbbolt_signup_cancelled', $content ) )
			$display .= do_shortcode( '[bbbolt_signup_cancelled]' );

		if( ! $this->contains_shortcode( 'bbbolt_logged_in', $content ) )
			$display .= do_shortcode( '[bbbolt_logged_in]' );

		$display .= '</div>';

		return apply_filters( 'bbbolt_registration_page_shortcode_content', $display, $attributes, $content );
	}


	/**
	 * Parse the content specified between the bbbolt_registration_form shortcode and return it as part of 
	 * the content if a user is not logged in and in the middle of the signup process. 
	 */
	function shortcode_registration_form_handler( $attributes, $content = '' ) {
		global $wp_query; 

		extract( shortcode_atts( array(
			'id' => 'bbbolt-registration-form',
			'class' => '',
		), $attributes ) );

		if( ! is_user_logged_in() && @$wp_query->query_vars['bbbolt'] != 'register-user' && @$wp_query->query_vars['bbbolt'] != 'payment-cancelled' ) {
			$display = "<div id='$id' class='$class'>";

			$display .= do_shortcode( $content );

			$display .= $this->get_registration_form( '', get_permalink() ) . $this->get_scripts();

			$display .= '</div>';
		} else {
			$display = '';
		}

		return apply_filters( 'bbbolt_registration_form_shortcode_content', $display, $attributes, $content );
	}


	/**
	 * Parse the content specified between the bbbolt_signup_complete shortcode and return it as part of 
	 * the content if a user has just completed the sign-up process. 
	 */
	function shortcode_signup_complete_handler( $attributes, $content = '' ) {
		global $wp_query; 

		if( isset( $wp_query->query_vars['bbbolt'] ) && $wp_query->query_vars['bbbolt'] == 'register-user' ){
			$display = ( ! empty( $content ) ) ? do_shortcode( $content ) : '<p class="message">' . __( 'Sign-up Complete!', 'bbbolt' ) . '</p>';
		} else {
			$display = '';
		}

		return apply_filters( 'bbbolt_signup_complete_shortcode_content', $display, $attributes, $content );
	}


	/**
	 * Parse the content specified between the bbbolt_signup_cancelled shortcode and return it as part of 
	 * the content if a user has cancelled payment. 
	 */
	function shortcode_signup_cancelled_handler( $attributes, $content = '' ) {
		global $wp_query; 

		if( isset( $wp_query->query_vars['bbbolt'] ) && $wp_query->query_vars['bbbolt'] == 'payment-cancelled' ){
			$display = ( ! empty( $content ) ) ? do_shortcode( $content ) : $this->payment_cancelled( get_permalink() );
		} else {
			$display = '';
		}

		return apply_filters( 'bbbolt_signup_cancelled_shortcode_content', $display, $attributes, $content );
	}


	/**
	 * Parse the content specified between the bbbolt_logged_in shortcode and return it as part of the content if a user
	 * is logged in. 
	 */
	function shortcode_logged_in_handler( $attributes, $content = '' ) {

		// User is logged in and has not just completed the sign-up process
		if( is_user_logged_in() && ! ( isset( $wp_query->query_vars['bbbolt'] ) && $wp_query->query_vars['bbbolt'] == 'register-user' ) )
			$display = ( ! empty( $content ) ) ? do_shortcode( $content ) : '<p class="message">' . __( 'You are logged in.', 'bbbolt' ) . '</p>';
		else
			$display = '';

		return apply_filters( 'bbbolt_logged_in_shortcode_content', $display, $attributes, $content );
	}


	/**
	 * Output a description of the subscription, optionally within enclosed content using {$variables} to represent
	 * string parameters.
	 */
	function shortcode_subscription_details_handler( $attributes, $content = '' ) {

		extract( shortcode_atts( array(
			'id' => 'bbbolt-subscription-details',
			'class' => '',
		), $attributes ) );

		if( ! empty( $content ) ) {
			foreach( $this->subscription as $detail => $value ) {

				if( in_array( $detail, array( 'amount', 'initial_amount', 'trial_amount' ) ) ) // Prefix a currency symbol to currency items
					$value = $this->paypal->get_currency_symbol() . $value;

				if( is_string( $value ) )
					$value = strtolower( $value );

				$content = str_replace( '{$'.$detail.'}', $value, $content );
			}

			$content = do_shortcode( $content );

		} else {

			$content = "<div id='$id' class='$class'>" . $this->get_subscription_details() . "</div>";

		}

		return $content;
	}


	/* HELPER FUNCTIONS */


	/**
	 * Check if current page includes the bbBolt registration Shortcode
	 */
	function contains_shortcode( $shortcode = 'bbbolt_registration_page', $content = '' ){
		global $post;

		if( empty( $content ) && is_object( $post ) )
			$content = $post->post_content;

		$shortcode = str_replace( array( '[', ']' ), '', $shortcode );

		if( strpos( $content, $shortcode ) !== false ) // Speed over accuracy
			return true;
		else
			return false;
	}

	/**
	 * Get the URL for the server. 
	 * 
	 * Takes care of passing messages if set in $bbb_message global.
	 * 
	 * @param args string|array If a string, the bbbolt URI parameter is set to the value of the string, if an array, the complete set of args at set as parameters on the array.
	 */
	function get_url( $args = array( 'bbbolt' => 'home' ) ){

		if( ! is_array( $args ) )
			$args = array( 'bbbolt' => $args );

		$url = add_query_arg( $args, $this->site_url );

		if( $this->get_messages() )
			$url = add_query_arg( array( 'bbb-msg' => urlencode( $this->get_messages() ) ), $url );

		// Add front-end flags to redirect the current user back to the same page they registered on
		if( $args['bbbolt'] == 'checkout' && get_permalink() != false )
			$url = add_query_arg( array( 'redirect_to' => urlencode( get_permalink() ) ), $url );

		return apply_filters( 'bbbolt_server_url', $url, $args );
	}


	/**
	 * Returns the URL to the location of this file's parent folder.
	 * 
	 * Useful for enqueuing scripts, styles & images without hardcoding the URL. 
	 * Allows the bbbolt directory to be located anywhere in a plugin.
	 */
	function get_dir_url() {
		$path_after_plugin_dir = explode( 'plugins', dirname( __FILE__ ) );
		return plugins_url() . $path_after_plugin_dir[1];
	}


	/**
	 * Check to make sure bbPress is activate as the server relies on it.
	 **/
	function check_requirements(){

		if( ! class_exists( 'bbPress' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );	// Need deactivate_plugins()
			deactivate_plugins( $this->registering_plugin );
			wp_die( sprintf( __( 'The %sbbPress plugin%s must be active for %sbbBolt%s to work its magic. bbBolt has been deactivated. %sInstall & activate bbPress%s', 'bbbolt' ), 
							 '<a href="http://wordpress.org/extend/plugins/bbpress/">', '</a>', 
							 '<a href="http://bbbolt.org/">', '</a>', 
							 '<br/><a href="' . admin_url('plugins.php') . '">', '&nbsp;&raquo;</a>' 
					)
			);
		}
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

		if ( ! isset( $rules['bbbolt/(.*)'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}


	/**
	 * Add bbBolt query var.
	 **/
	function query_var( $vars ) {
		$vars[] = 'bbbolt';
		return $vars;
	}


	/**
	 * Simple Encryption Function
	 **/
	function encrypt( $text ) { 
		if( !defined( 'BBBOLT_KEY' ) )
			define( 'BBBOLT_KEY',  substr( AUTH_KEY, 0, mcrypt_module_get_algo_key_size( MCRYPT_RIJNDAEL_256 ) ) );

		if( function_exists( 'mcrypt_ecb' ) ) {
			$encrypted_text = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, BBBOLT_KEY, $text, MCRYPT_MODE_ECB, mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), MCRYPT_RAND ) );
		} else {
		  for( $i = 0; $i < strlen( $text ); $i++ ) {
			$char            = substr( $text, $i, 1 );
			$keychar         = substr( BBBOLT_KEY, ( $i % strlen( BBBOLT_KEY ) )-1, 1 );
			$char            = chr( ord( $char ) + ord( $keychar ) );
			$encrypted_text .= $char;
		  }
		}

		return trim( base64_encode( $encrypted_text ) );
	} 

	/**
	 * Simple Decryption Function
	 **/
	function decrypt( $text ) {
		if( !defined( 'BBBOLT_KEY' ) )
			define( 'BBBOLT_KEY',  substr( AUTH_KEY, 0, mcrypt_module_get_algo_key_size( MCRYPT_RIJNDAEL_256 ) ) );

		$text = base64_decode( $text );

		if( function_exists( 'mcrypt_ecb' ) ){
			$decrypted_text = trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, BBBOLT_KEY, $text, MCRYPT_MODE_ECB, mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), MCRYPT_RAND ) ) );
		} else {
		  for( $i = 0; $i < strlen( $text ); $i++ ) {
			$char            = substr( $text, $i, 1 );
			$keychar         = substr( BBBOLT_KEY, ( $i % strlen( BBBOLT_KEY ) ) - 1, 1 );
			$char            = chr( ord( $char ) - ord( $keychar ) );
			$decrypted_text .= $char;
		  }
		}

		return $decrypted_text;
	} 


	/**
	 * Overrides the value of the 'users_can_register' site option to always 
	 * return false. This returns false on both single & multisite installs due to the hooks priority. 
	 */
	function users_can_register_override() {
		return false;
	}

}
endif;


if( ! function_exists( 'register_bbbolt_server' ) ) :
/**
 * Register a bbBolt Server for your site. Do not use before init or with init priority later than 10.
 *
 * A function for creating a bbBolt server.
 * 
 * The function will accept an array (second optional parameter), 
 * along with a string for the URL of the site running bbPress.
 *
 * Optional $args contents:
 **/
function register_bbbolt_server( $name, $paypal_credentials, $args = array() ){

	// If you are using a custom bbBolt Server Class, hook into this filter
	$bbbolt_server_class = apply_filters( 'bbBolt_Server_Class', 'bbBolt_Server' );

	$bbbolt_server = new $bbbolt_server_class( $name, $paypal_credentials, $args );
}
endif;


