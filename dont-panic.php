<?php

/**
 * New/Edit Topic
 *
 * @package Tyrolean
 */
?>

<?php if ( ( bbp_is_topic_edit() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) || current_user_can( 'publish_topics' ) || ( bbp_allow_anonymous() && !is_user_logged_in() ) ) : ?>

	<?php if ( ( !bbp_is_forum_category() && ( !bbp_is_forum_closed() || current_user_can( 'edit_forum', bbp_get_topic_forum_id() ) ) ) || bbp_is_topic_edit() ) : ?>

		<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form">

			<form id="new_post" name="new_post" method="post" action="">
				<fieldset>
					<legend>
						<?php
							if ( bbp_is_topic_edit() )
								printf( __( 'Edit topic "%s"', 'bbpress' ), bbp_get_topic_title() );
							else
								bbp_is_forum() ? printf( __( 'Post new topic in: &ldquo;%s&rdquo;', 'tyrolean' ), bbp_get_forum_title() ) : _e( 'New ticket', 'tyrolean' );
						?>
					</legend>

					<?php if ( !bbp_is_topic_edit() && bbp_is_forum_closed() ) : ?>
						<div class="bbp-template-notice">
							<p><?php _e( 'This forum is marked as closed to new topics, however your posting capabilities still allow you to do so.', 'tyrolean' ); ?></p>
						</div>
					<?php endif; ?>

					<div>
						<?php if ( !bbp_is_forum() ) : ?>
							<p>
								<label for="bbp_forum_id"><?php _e( 'Forum:', 'tyrolean' ); ?></label><br />
								<?php bbp_dropdown( array( 'selected' => bbp_get_form_topic_forum() ) ); ?>
							</p>
						<?php endif; ?>

						<?php bbp_get_template_part( 'bbpress/form', 'anonymous' ); ?>

						<p>
							<label for="bbp_topic_title"><?php _e( 'Subject:', 'bbpress' ); ?></label><br />
							<input type="text" id="bbp_topic_title" value="<?php bbp_form_topic_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_title" />
						</p>

						<p>
							<label for="bbp_topic_content"><?php _e( 'Message:', 'tyrolean' ); ?></label><br />
							<textarea id="bbp_topic_content" tabindex="<?php bbp_tab_index(); ?>" name="bbp_topic_content" cols="51" rows="6"><?php bbp_form_topic_content(); ?></textarea>
						</p>

						<?php if ( !bbp_is_topic_edit() ) : ?>
							<p>
								<label for="bbp_topic_tags"><?php _e( 'Tags:', 'tyrolean' ); ?></label><br />
								<input type="text" value="<?php bbp_form_topic_tags(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_tags" id="bbp_topic_tags" />
							</p>
						<?php endif; ?>

						<?php if ( bbp_is_subscriptions_active() && !bbp_is_anonymous() && ( !bbp_is_topic_edit() || ( bbp_is_topic_edit() && !bbp_is_topic_anonymous() ) ) ) : ?>
							<p>
								<input name="bbp_topic_subscription" id="bbp_topic_subscription" type="checkbox" value="bbp_subscribe" <?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />
								<?php if ( bbp_is_topic_edit() && ( $post->post_author != bbp_get_current_user_id() ) ) : ?>
									<label for="bbp_topic_subscription"><?php _e( 'Notify the author of replies via email', 'tyrolean' ); ?></label>
								<?php else : ?>
									<label for="bbp_topic_subscription"><?php _e( 'Notify me of replies via email', 'tyrolean' ); ?></label>
								<?php endif; ?>
							</p>
						<?php endif; ?>

						<?php if ( bbp_is_topic_edit() ) : ?>
							<fieldset>
								<legend><?php _e( 'Revision', 'tyrolean' ); ?></legend>
								<div>
									<input name="bbp_log_topic_edit" id="bbp_log_topic_edit" type="checkbox" value="1" <?php bbp_form_topic_log_edit(); ?> tabindex="<?php bbp_tab_index(); ?>" />
									<label for="bbp_log_topic_edit"><?php _e( 'Keep a log of this edit:', 'tyrolean' ); ?></label><br />
								</div>

								<div>
									<label for="bbp_topic_edit_reason"><?php printf( __( 'Optional reason for editing:', 'tyrolean' ), bbp_get_current_user_name() ); ?></label><br />
									<input type="text" value="<?php bbp_form_topic_edit_reason(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_edit_reason" id="bbp_topic_edit_reason" />
								</div>
							</fieldset>
						<?php endif; ?>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_topic_submit" name="bbp_topic_submit"><?php _e( 'Submit', 'tyrolean' ); ?></button>
						</div>
					</div>

					<?php bbp_topic_form_fields(); ?>

				</fieldset>
			</form>
		</div>

	<?php elseif ( bbp_is_forum_closed() ) : ?>

		<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
			<h2 class="entry-title"><?php _e( 'Sorry!', 'tyrolean' ); ?></h2>
			<div class="bbp-template-notice">
				<p><?php _e( 'This forum is closed to new topics.', 'tyrolean' ); ?></p>
			</div>
		</div>

	<?php endif; ?>

<?php else : ?>

	<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
		<h2 class="entry-title"><?php _e( 'Sorry!', 'tyrolean' ); ?></h2>
		<div class="bbp-template-notice">
			<p><?php is_user_logged_in() ? _e( 'You cannot create new topics at this time.', 'tyrolean' ) : _e( 'You must be logged in to create new topics.', 'tyrolean' ); ?></p>
		</div>
	</div>


<?php endif; ?>

