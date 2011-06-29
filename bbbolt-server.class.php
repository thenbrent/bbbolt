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
			'site_url' => get_site_url(),
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
			)
		);

		$args = wp_parse_args( $args, $defaults );

		if( empty( $args['registering_plugin'] ) ){ // Get the handle fo the calling plugin
			$backtrace = debug_backtrace();
			$args['registering_plugin'] = basename( dirname( $backtrace[1]['file'] ) ) . '/' . basename( $backtrace[1]['file'] );
		}

		$this->name               = sanitize_key( $name );
		$this->site_url           = $args['site_url'];
		$this->labels             = (object)$args['labels'];
		$this->bbbolt_url         = add_query_arg( array( 'bbbolt' => 1 ), $args['site_url'].'/' );
		$this->registering_plugin = $args['registering_plugin'];

		$this->subscription       = (object)$args['paypal']['subscription'];

		$this->paypal             = new PayPal_Digital_Goods( $paypal_credentials, $args['paypal'] );

		add_filter( 'query_vars', array( &$this, 'query_var' ) );

		add_action( 'init', array( &$this, 'check_requirements' ), 11 );
		add_action( 'init', array( &$this, 'flush_rules' ), 12 );
		add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( &$this, 'request_handler' ), -1 );
		add_filter( 'status_header', array( &$this, 'unset_404' ), 10, 4 );
	}


	/**
	 * Routes requests and chooses which view to display
	 */
	function request_handler(){
		global $wp_query, $bbb_message;

		// Don't touch non bbbolt queries
		if( ! isset( $wp_query->query_vars['bbbolt'] ) )
			return;

		// New user Registration
		if( isset( $_POST['bbb-registration'] ) ) {

			$this->register_user();

		} elseif( isset( $_POST['bbb_topic_submit'] ) ) {

			// No Simple save function for bbPress, bbp_new_topic_handler does the save but also does a redirect, so we need to force it to redirect back to us.
			$bbb_message = 'Thanks for your submission. We will reply soon.';
			add_filter( 'bbp_new_topic_redirect_to', array( &$this, 'get_url' ) );
			bbp_new_topic_handler();

		} else {

			// Page View
			$this->get_header();

			// Get the user to login or signup
			if( ! is_user_logged_in() ) {

				$this->signup_process();

			} elseif( $wp_query->query_vars['bbbolt'] == 'inbox' ) {

				$this->support_inbox();

			} else { // Default to new topic form

				$this->support_form();

			}

			$this->get_footer();

		}
		exit();
	}


	/**
	 * Takes care of the signup user flow.
	 */
	function signup_process(){ 
		global $wp_query;
		?>
		<div id="register-container">
		<?php if( ! get_option( 'users_can_register' ) ) : ?>

			<h3><?php printf( __( 'Registrations for %s are Closed', 'bbbolt' ), $this->labels->name ); ?></h3>
			<p><?php printf( __( 'Please contact the %s developers to request they open registration on %s.', 'bbbolt' ), $this->labels->name, '<a href="'.$this->site_url.'" target="_blank">'.$this->site_url.'</a>' ); ?></p>

		<?php elseif( $wp_query->query_vars['bbbolt'] == 'paypal' && isset( $_GET['return'] ) ) : // Subscriber returning from PayPal Payment ?>

			<?php // If we're still in the PayPal iframe, remove it and reload the parent page ?>
			<script>
				if(parent!=window.top) {
					parent.location.href = document.location;
				}
			</script>

			<?php if( $_GET['return'] == 'paid' ) {

				$checkout_details = $this->paypal->get_checkout_details();

				$this->register_form( array( 'username' => $this->make_username_from_email( $checkout_details['EMAIL'] ), 'email' => urldecode( $checkout_details['EMAIL'] ) ) );

				} elseif( $_GET['return'] == 'cancel' ) { ?>

				<h3><?php printf( __( '%s Sign-Up Cancelled', 'bbbolt' ), $this->labels->name ); ?></h3>
				<p><?php _e( 'You have successfully terminated the subscription process.', 'bbbolt' ); ?></p>
				<p><?php printf( __( 'You can attempt to sign-up again %shere%s.', 'bbbolt' ), '<a href="'.$this->bbbolt_url.'">', '</a>' ); ?></p>

			<?php } ?>
		<?php else : // Output Sign-up blurb ?>

			<h3><?php printf( __( 'Sign-Up with %s', 'bbbolt' ), $this->labels->name ); ?></h3>
			<p><?php printf( __( 'Get exclusive access to premium support and influence over the future of %s.', 'bbbolt' ), $this->labels->name ); ?></p>
			<p><?php printf( __( 'All for only $%s per %s.', 'bbbolt' ), $this->subscription->amount, $this->subscription->period ); ?></p>
			<p><?php printf( __( 'To sign-up, you must first authorize %s to collect recurring payments via PayPal. You can cancel this subscription at anytime.', 'bbbolt' ), $this->labels->name ); ?></p>
			<p class="submit">
				<?php $this->paypal->print_buy_button(); ?>
			</p>

		<?php endif; ?>

			<p id="already-member">
				<?php _e( 'Already have an account?', 'bbbolt' ); ?>&nbsp;<a id="login-link" href="<?php echo site_url('wp-login.php', 'login') ?>" title="<?php _e( 'Login', 'bbbolt' ) ?>"><?php _e( 'Login here.', 'bbbolt' ) ?></a>
			</p>
		</div>

		<?php $this->login_form(); ?>
		<?php
	}


	/* TEMPLATE FUNCTIONS */


	/**
	 * Outputs the HTML registration form for the plugin's support page.
	 *
	 * Calls the same hooks as the vanilla WordPress registration form to be compatible with 
	 * other plugins. 
	 **/
	function register_form( $credentials ) { 
		global $bbb_message;
		?>
		<h3><?php _e( 'Confirm Your Subscription', 'bbbolt' ); ?></h3>
		<?php if( isset( $bbb_message ) ) : ?>
			<div id="message" class="updated fade"><p><strong><?php echo $bbb_message; ?></strong></p></div>
		<?php endif; ?>
		<p><?php _e( 'Please enter your account details to complete the subscription.', 'bbbolt' ); ?></p>
		<form name="bbb-registerform" id="bbb-registerform" action="" method="post">
			<p>
				<label><?php _e( 'Username', 'bbbolt' ) ?></label>
				<input type="text" name="bbb-username" id="bbb-username" class="input" value="<?php echo esc_attr(stripslashes($credentials['username'])); ?>" size="25" tabindex="10" />
			</p>
			<p>
				<label><?php _e( 'E-mail', 'bbbolt' ) ?></label>
				<input type="text" name="bbb-email" id="bbb-email" class="input" value="<?php echo esc_attr(stripslashes($credentials['email'])); ?>" size="25" tabindex="20" />
			</p>
			<p>
				<label><?php _e( 'Password', 'bbbolt' ) ?></label>
				<input type="password" name="bbb-password" id="bbb-password" class="input" value="" size="25" tabindex="30" />
			</p>
			<p><?php printf( __( 'Total: $%s per %s', 'bbbolt' ), $this->subscription->amount, $this->subscription->period ); ?></p>
			<?php do_action( 'register_form' ); ?>
			<?php wp_nonce_field( __FILE__, 'bbb-nonce' ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
			<p class="submit">
				<input type="submit" name="bbb-registration" id="bbb-registration" class="button-primary" value="<?php esc_attr_e( 'Sign-Up Now', 'bbbolt' ); ?>" tabindex="100" />
			</p>		
		</form>
	<?php
	}


	/**
	 * Outputs the HTML login form for the plugin's support page.
	 **/
	function login_form() { ?>
		<div id="login-container" style="display:none;">
			<h3><?php _e( 'Login', 'bbbolt' ); ?></h3>
			<p><?php printf( __( 'Login to the %s support system.', 'bbbolt' ), $this->labels->name ); ?></p>
			<?php wp_login_form( array( 'redirect' => esc_url( $_SERVER['REQUEST_URI'] ) ) ); ?>
			<a id="forgot-link" href="<?php echo $this->bbbolt_url ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
		</div>
		<div id="forgot-container" style="display:none;">
			<form name="lostpasswordform" id="lostpasswordform" action="" method="post">
				<p>
					<label><?php _e('Username or E-mail:') ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="10" />
				</p>
			<?php do_action('lostpassword_form'); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Get New Password'); ?>" tabindex="100" /></p>
			</form>
		</div>
	<?php
	}


	/**
	 * Register a new user with the site. Log them in and redirect them to the support form (with a message notifying them of successful subscription)
	 */
	function register_user(){
		global $bbb_message;

		if( ! wp_verify_nonce( $_POST['bbb-nonce'], __FILE__ ) ) 
			die( 'Nonce Security Check Failed. Please try again or contact the site administrator.' );

		// Create User
		$user_id = wp_create_user( $_POST['bbb-username'], $_POST['bbb-password'], $_POST['bbb-email'] );

		// Make sure the user was created successfully
		if( is_wp_error( $user_id ) ) {
			$bbb_message = $user_id->get_error_message();
			$this->get_header();
			$this->register_form( array( 'username' => $_POST['bbb-username'], 'email' => $_POST['bbb-email'] ) );
			$this->get_footer();
			return;
		}

		// Log the new user in
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		// Create the Recurring Payment Profile with PayPal
		$response = $this->paypal->start_subscription();

		// Make sure the request with PayPal succeeded
		if( $response['ACK'] == 'Failure' || $response['ACK'] == 'FailureWithWarning' || ! isset( $response['PROFILEID'] ) ){
			// Remove the User's Role
			$user = new WP_User($user_id);
			$user->set_role('');
			// Immediately exit and notify of the error.
			die( 'We were unable to commence your subscription. PayPal error: ' . urldecode( $response['L_LONGMESSAGE0'] ) );
		}

		// Store the user's Payment Profile ID 
		update_user_meta( $user_id, 'paypal_payment_profile_id', urldecode( $response['PROFILEID'] ) );

		$bbb_message = "Your account have been created. Thanks for signing up.";

		// Don't run the registration process again
		unset( $_POST['bbb-registration'] );
		$this->request_handler();
	}


	/**
	 * Display the support form
	 */
	function support_form() { ?>
		
		<h3><?php _e( "New Ticket", 'bbbolt' ); ?></h3>

		<?php if( $this->get_messages() ) : ?>
			<div id="message" class="updated fade"><p><strong><?php echo $this->get_messages(); ?></strong></p></div>
		<?php endif; ?>

		<?php if ( ( bbp_is_topic_edit() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) || current_user_can( 'publish_topics' ) || ( bbp_allow_anonymous() && !is_user_logged_in() ) ) : ?>

			<?php if ( ( !bbp_is_forum_category() && ( !bbp_is_forum_closed() || current_user_can( 'edit_forum', bbp_get_topic_forum_id() ) ) ) || bbp_is_topic_edit() ) : ?>

				<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form">

					<form id="new-post" name="new-post" method="post" action="">

							<?php do_action( 'bbp_template_notices' ); ?>

							<div>
								<?php if ( ! bbp_is_forum() && bbp_get_dropdown( array( 'selected' => bbp_get_form_topic_forum() ) ) != 'No forums available' ) : ?>
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
							<th id="bbb_freshness"><?php _e( 'Subject', 'bbbolt' ); ?></th>
							<th id="bbb_freshness"><?php _e( 'Message', 'bbbolt' ); ?></th>
							<th id="bbb_freshness"><?php _e( 'Author', 'bbbolt' ); ?></th>
							<th id="bbb_freshness"><?php _e( 'Freshness', 'bbbolt' ); ?></th>
							<th id="bbb_freshness"><?php _e( 'Reply', 'bbbolt' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<th id="bbb_freshness"><?php _e( 'Subject', 'bbbolt' ); ?></th>
						<th id="bbb_freshness"><?php _e( 'Message', 'bbbolt' ); ?></th>
						<th id="bbb_freshness"><?php _e( 'Author', 'bbbolt' ); ?></th>
						<th id="bbb_freshness"><?php _e( 'Freshness', 'bbbolt' ); ?></th>
						<th id="bbb_freshness"><?php _e( 'Reply', 'bbbolt' ); ?></th>
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
				color: #E69100;
				background: #F8E1B9;
				border: 1px solid #E69100;
				padding: 2px 5px;
			}
			#bbb-registerform .strengthy-valid {
				color: #2C8A00;
				background: #ECF4E8;
				border: 1px solid #2C8A00;
				padding: 2px 5px;
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
				border-color: #E6DB55;
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
	 * Print the scripts used to enhance different parts of the login/registration/form submission process.
	 */
	function print_scripts() { ?>
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

				// Dim the Registration frame when PayPal pops up
				$('#paypal-submit').click(function(){
					$('#register-container, .bbbolt-title').fadeTo(0,0.2);
				});

				// Attach password strength meter to the registration form
				$('#bbb-password').strengthy({ minLength: 5, msgs: [ 'Weak', 'Weak', 'OK', 'OK', 'Strong', 'Show password' ] });
			});
		</script>
		<?php
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


	/* HELPER FUNCTIONS */


	/**
	 * Get the URL for the server
	 */
	function get_url( $args = array( 'bbbolt' => 'home' ) ){

		$url = add_query_arg( $args, $this->site_url );

		if( $this->get_messages() )
			$url = add_query_arg( array( 'bbb-msg' => urlencode( $this->get_messages() ) ) );

		return apply_filters( 'bbbolt_server_url', $url );
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

		if( ! defined( 'BBP_VERSION' ) ) {
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

		if ( ! isset( $rules['bbbolt/(.+)'] ) ) {
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
	 * Strips the string from an email that preceeds the @ character and sanitizes it. 
	 */
	function make_username_from_email( $email ){
		$username = explode( '@', urldecode( $email ) );

		return sanitize_user( $username[0] );
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


