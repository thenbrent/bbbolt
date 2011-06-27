<?php 


if( ! class_exists( 'bbBolt_Client_UI' ) ) :
/**
 * bbBolt UI Singleton
 * 
 * Every client needs an interface; however, most UI functions only need to be performed
 * once, so a singleton class is suitable and called in the bbBolt Client.
 **/
class bbBolt_Client_UI {
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
	 * Output the Support Inbox Administration Page for the Current User
	 **/
	public function support_inbox(){
		global $bbbolt_clients;
		?>
		<div id="bbb-support-inbox" class="wrap">
			<?php screen_icon( 'users' ); ?>
			<h2><?php _e( 'Support Inbox', 'bbbolt' ); ?></h2>
			<div id="bbb-support-inbox">
				<?php if ( count( $bbbolt_clients ) > 1 ) : ?>
					<?php $iframe_src = 'about:blank'; ?>
					<p><?php _e( 'Thanks for your call, to help us direct your call, please select the plugin for which you want to make a support request.', 'bbbolt' ); ?></p>
					<ul class="bbb-client-list">
					<?php foreach( $bbbolt_clients as $client ) : ?>
						<li>&raquo;&nbsp;<a href="<?php echo $client->get_url( 'inbox' ); ?>" target="bbbolt-inbox-frame"><?php echo $client->get_name(); ?></a></li>
					<?php endforeach; ?>
					</ul>
				<?php else : ?>
				<?php $iframe_src = $bbbolt_clients[0]->get_url(); ?>
				<?php endif; ?>
				<img id="bbb-inbox-loading" src="<?php echo get_bbbolt_dir_url(); ?>/images/loader.gif">
				<iframe id="bbbolt-inbox-frame" name="bbbolt-inbox-frame" class="bbbolt-frame autoHeight" src="<?php echo $iframe_src; ?>" width="100%" scrolling="no">
					<p><?php _e( "Uh oh, your browser does not support iframes. Please upgrade to a modern browser.", "bbbolt") ?></p>
				</iframe>
			</div>
		</div>

	<?php
	}


	/**
	 * Output the support form for this client (or an intermediary form if there are multiple clients.)
	 **/
	public function support_form(){
		global $bbbolt_clients;
		?>
		<div id="bbb-support-form">
			<h2 class="bbbolt-title"><?php _e( 'Get Support', 'bbbolt' ); ?></h2>
		<?php if ( count( $bbbolt_clients ) > 1 ) : ?>
			<?php $iframe_src = 'about:blank'; ?>
			<p><?php _e( 'Thanks for your call, to help us direct your call, please select the plugin for which you want to make a support request.', 'bbbolt' ); ?></p>
			<ul class="bbb-client-list">
			<?php foreach( $bbbolt_clients as $client ) : ?>
				<li>&raquo;&nbsp;<a href="<?php echo $client->get_url( 'form' ); ?>" target="bbbolt-form-frame"><?php echo $client->get_name(); ?></a></li>
			<?php endforeach; ?>
			</ul>
		<?php else : ?>
		<?php $iframe_src = $bbbolt_clients[0]->get_url(); ?>
		<?php endif; ?>
			<img id="bbb-form-loading" src="<?php echo get_bbbolt_dir_url(); ?>/images/loader.gif">
			<iframe id="bbbolt-form-frame" name="bbbolt-form-frame" class="bbbolt-frame" src="<?php echo $iframe_src; ?>" width="100%" height="100%" scrolling="no">
				<p><?php _e( "Uh oh, your browser does not support iframes. Please upgrade to a modern browser.", "bbbolt") ?></p>
			</iframe>
			<div id="power-bbbolt">Powered by <a href="http://bbbolt.org">Thunder &amp; Lightning</a>.
		</div>
		<?php
	}


	/**
	 * Define the extra markup required to create a slider on every page of the 
	 * WordPress Administration.
	 **/
	public function support_form_slider() { ?>
		<div id="bbb-support-slider">
			<div id="bbb-support-toggle"><a href="#">&lt;</a></div>
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
		#bbb-support-slider {
			height: 100%;
			width:460px;
			position: fixed;
			right:-460px;
			top: 0;
		}

		#bbb-support-slider #bbb-support-toggle {
			background: #ECECEC;
			border: 1px solid #CCC;
			font-family: Arial, Helvetica, Verdana, sans-serif;
			width: 10px;
			height: 30px;
			float: left;
			position: relative;
			left: -13px;
			top: 40%;
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

		#bbb-support-slider #bbb-support-toggle a {
			font-weight: bold;
			text-decoration: none;
		}

		#bbb-support-slider #bbb-support-form {
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

		.bbbolt-frame.loading {
			opacity:0.2;
			overflow: hidden;
			min-height: 300px;
		}

		img#bbb-form-loading,
		img#bbb-inbox-loading {
			display: none;
			position: absolute;
			top: 30%;
			left: 25%;
			z-index: 999;
		}

		.bbb-client-list {
			border-bottom: 1px solid #CCC;
			padding-bottom: 10px;
		}

		.bbb-client-list li {
			display: inline-block;
			list-style-type: none;
			margin-right: 10px;
			padding-left: 4px;
			width: 140px;
		}

		#power-bbbolt {
			font-size: 0.8em;
			position: absolute;
			bottom: 25px;
			right: 5px;
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
			// Sliding Support Form
			$('#bbb-support-slider #bbb-support-toggle').click(function() {
				var $righty = $('#bbb-support-slider');
				$righty.animate({ right: parseInt($righty.css('right'),10) == 0 ? -$righty.outerWidth() : 0});
				if($('#bbb-support-toggle a').text() == '<'){
					$('#bbb-support-toggle a').text('>');
					$('#wpcontent, #adminmenuwrap, #footer').fadeTo('fast',0.4);
				} else {
					$('#bbb-support-toggle a').text('<')
					$('#wpcontent, #adminmenuwrap, #footer').fadeTo('fast',1);
				}
				return false;
			});

			// Loading animation for form iframe
			$('#bbb-support-form .bbb-client-list a').click(function(){
				$('#bbbolt-form-frame').addClass('loading');
				$('#bbbolt-form-frame').attr('src',$(this).attr('href'));
				$('#bbb-form-loading').show();
			});

			// Loading animation for inbox iframe
			$('#bbb-support-inbox .bbb-client-list a').click(function(){
				$('#bbbolt-inbox-frame').addClass('loading');
				$('#bbbolt-inbox-frame').attr('src',$(this).attr('href'));
				$('#bbb-inbox-loading').show();
			});

			$('.bbbolt-frame').load(function(){
				// Finish loading animation on frames
				$(this).removeClass('loading');
				$('#bbb-form-loading, #bbb-inbox-loading').hide();
				// Resize form to fit content without causing an iframe cross domain error
				if(document.location.hostname == $(this).attr('src').substring(7,7+document.location.hostname.length)) {
					// Enforce a minimum size of 400 as required by Paypal Popup
					var new_height = 400;
					if($(this).contents().find('html').height() > 400){
						new_height = $(this).contents().find('html').height();
					}
					$(this).height(new_height);
				} else { // Just make it tall
					$(this).height('600px');
				}
			});
		});
		</script>
	<?php
	}


}
endif;
