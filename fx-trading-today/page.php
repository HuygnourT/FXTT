<?php
/**
 * Default page: hero (title + excerpt as lead) and readable-width content.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$fxt_parts = fxt_tt_content_parts();
	fxt_tt_the_page_hero( array( 'extra' => $fxt_parts['hero'] ) );
	?>
	<div class="entry-content prose page-body has-global-padding is-layout-constrained">
		<?php echo $fxt_parts['body']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content. ?>
		<?php wp_link_pages(); ?>
	</div>
	<?php
endwhile;
get_footer();
