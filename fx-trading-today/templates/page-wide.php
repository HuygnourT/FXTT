<?php
/**
 * Template Name: Wide (tools and listings)
 * Template Post Type: page
 *
 * For pages built around interactive blocks (directory, comparison, evidence
 * index): content uses the full container width, sections can still be full
 * width.
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
	<div class="entry-content page-body page-body--wide has-global-padding is-layout-constrained">
		<?php echo $fxt_parts['body']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content. ?>
	</div>
	<?php
endwhile;
get_footer();
