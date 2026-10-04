<?php
/**
 * Comments (articles only; reviews and evidence do not take comments).
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section class="comments" id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="doc-section__title">
			<?php
			/* translators: %d: number of comments */
			echo esc_html( sprintf( _n( '%d comment', '%d comments', get_comments_number(), 'fx-trading-today' ), get_comments_number() ) );
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
