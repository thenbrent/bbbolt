<?php
/**
 * Feed management for bbBolt
 *
 * @package bbBolt
 */


if( !function_exists( 'fetch_feed' ) )
	require_once (ABSPATH . WPINC . '/feed.php');


global $site_url, $forums_feed_url, $topics_feed_url;
$site_url 	 = 'http://test.brentshepherd.com/forums/';
$forums_feed_url = $site_url . 'forum/feed';
$topics_feed_url = $site_url . 'topic/feed';

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

// Set feed cache for support posts to be five minutes
function bbb_feed_lifetime( $duration, $url ){
	global $forums_feed_url, $topics_feed_url;

	if( $url == $forums_feed_url || $url == $topics_feed_url )
		return 300;
	else
		return $duration;
}
add_filter( 'wp_feed_cache_transient_lifetime', 'bbb_feed_lifetime', 10, 2 );

