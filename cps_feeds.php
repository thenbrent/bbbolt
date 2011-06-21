<?php
/**
 * Feed management for bbBolt
 *
 * @package bbBolt
 */


if( !function_exists( 'fetch_feed' ) )
	require_once (ABSPATH . WPINC . '/feed.php');


global $forums_url, $forums_feed_url, $topics_feed_url;
$forums_url 	 = 'http://test.brentshepherd.com/forums/';
$forums_feed_url = $forums_url . 'forum/feed';
$topics_feed_url = $forums_url . 'topic/feed';

function bbb_add_menu_page(){
	add_menu_page( 'Code Poets Society', 'Code Poets', 'read', 'bbbolt', 'bbb_feeds_page' );
}
add_action( 'admin_menu', 'bbb_add_menu_page' );

function bbb_feeds_page(){
	global $forums_feed_url, $topics_feed_url;
	?>
	<div class="wrap">
	<?php
	$forums = fetch_feed( $forums_feed_url );
	echo '<h1>Forums</h1>';
	foreach( $forums->get_items() as $item ){ ?>
		<div class="item">
			<h2><a href="<?php echo $item->get_permalink(); ?>"><?php echo $item->get_title(); ?></a></h2>
			<p><?php echo $item->get_description(); ?></p>
			<p><small>Posted by <?php echo $item->get_author()->get_name(); ?> on <?php echo $item->get_date('j F Y | g:i a'); ?></small></p>
		</div>
	<?php }

	$topics = fetch_feed( $topics_feed_url );
	echo '<h1>Topics</h1>';
	foreach( $topics->get_items() as $item ){ ?>
		<div class="item">
			<h2><a href="<?php echo $item->get_permalink(); ?>"><?php echo $item->get_title(); ?></a></h2>
			<p><?php echo $item->get_description(); ?></p>
			<p><small>Posted by <?php echo $item->get_author()->get_name(); ?> on <?php echo $item->get_date('j F Y | g:i a'); ?></small></p>
		</div>
	<?php } ?>
	</div>
	<?php
}

// Set feed cache for support posts to be one minute
function bbb_feed_lifetime( $duration, $url ){
	global $forums_feed_url, $topics_feed_url;

	if( $url == $forums_feed_url || $url == $topics_feed_url )
		return 60;
	else
		return $duration;
}
add_filter( 'wp_feed_cache_transient_lifetime', 't_feed_lifetime', 10, 2 );


function bbb_new_topic_form(){ ?>
	<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form">

		<form id="new_post" name="new_post" method="post" action="">
			<fieldset>
				<legend>
					<?php _e( 'Create new topic', 'bbbolt' ); ?>
				</legend>
				<div>

					<?php get_template_part( 'bbpress/form', 'anonymous' ); ?>

					<p>
						<label for="bbp_topic_title"><?php _e( 'Topic Title:', 'bbpress' ); ?></label><br />
						<input type="text" id="bbp_topic_title" value="<?php bbp_form_topic_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_title" />
					</p>

					<p>
						<label for="bbp_topic_content"><?php _e( 'Topic Description:', 'bbpress' ); ?></label><br />
						<textarea id="bbp_topic_content" tabindex="<?php bbp_tab_index(); ?>" name="bbp_topic_content" cols="52" rows="6"><?php bbp_form_topic_content(); ?></textarea>
					</p>

					<p class="form-allowed-tags">
						<label><?php _e( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:','bbpress' ); ?></label><br />
						<code><?php bbp_allowed_tags(); ?></code>
					</p>

						<p>
							<label for="bbp_topic_tags"><?php _e( 'Topic Tags:', 'bbpress' ); ?></label><br />
							<input type="text" value="<?php bbp_form_topic_tags(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_tags" id="bbp_topic_tags" />
						</p>

						<p>
							<label for="bbp_forum_id"><?php _e( 'Forum:', 'bbpress' ); ?></label><br />
							<?php bbp_dropdown( array( 'selected' => bbp_get_form_topic_forum() ) ); ?>
						</p>

					<?php if ( bbp_is_subscriptions_active() && !bbp_is_anonymous() && ( !bbp_is_topic_edit() || ( bbp_is_topic_edit() && !bbp_is_topic_anonymous() ) ) ) : ?>

						<p>
							<input name="bbp_topic_subscription" id="bbp_topic_subscription" type="checkbox" value="bbp_subscribe" <?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />

							<?php if ( bbp_is_topic_edit() && ( $post->post_author != bbp_get_current_user_id() ) ) : ?>

								<label for="bbp_topic_subscription"><?php _e( 'Notify the author of follow-up replies via email', 'bbpress' ); ?></label>

							<?php else : ?>

								<label for="bbp_topic_subscription"><?php _e( 'Notify me of follow-up replies via email', 'bbpress' ); ?></label>

							<?php endif; ?>
						</p>

					<?php endif; ?>

					<div class="bbp-submit-wrapper">
						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_topic_submit" name="bbp_topic_submit"><?php _e( 'Submit', 'bbpress' ); ?></button>
					</div>
				</div>

				<?php bbp_topic_form_fields(); ?>

			</fieldset>
		</form>
	</div>
<?php
}