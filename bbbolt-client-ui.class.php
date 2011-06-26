<?php 


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
			<ul id="bbb_client_list">
			<?php foreach( $bbbolt_clients as $client ) : ?>
				<li><a href="<?php echo $client->get_url(); ?>" target="bbbolt_frame"><?php echo $client->get_name(); ?></a></li>
			<?php endforeach; ?>
			</ul>
		<?php else : ?>
		<?php $iframe_src = $bbbolt_clients[0]->get_url(); ?>
		<?php endif; ?>
			<img id="loading" src="<?php echo get_bbbolt_dir_url(); ?>/images/loader.gif">
			<iframe id="bbbolt_frame" name="bbbolt_frame" src="<?php echo $iframe_src; ?>" width="100%" height="100%">
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
		
		#bbbolt_frame.loading {
			opacity:0.2;
		}
		
		img#loading {
			display: none;
			position: absolute;
			top: 30%;
			left: 25%;
			z-index: 999;
		}

		#bbb_client_list li {
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
			$('#bbb_support_slider #bbb_support_toggle').click(function() {
				var $righty = $('#bbb_support_slider');
				$righty.animate({ right: parseInt($righty.css('right'),10) == 0 ? -$righty.outerWidth() : 0});
				$('#bbb_support_toggle a').text() == '<' ? $('#bbb_support_toggle a').text('>') : $('#bbb_support_toggle a').text('<');
				return false;
			});

			// Loading animation for iframe
			$('#bbb_client_list a').click(function(){
				$('#bbbolt_frame').addClass('loading');
				$('#loading').show();
			});
		});
		</script>
	<?php
	}
}
