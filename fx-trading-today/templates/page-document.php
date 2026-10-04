<?php
/**
 * Template Name: Document with table of contents
 * Template Post Type: page
 *
 * Long-form pages (methodology, policies): byline, sticky table of contents
 * built from the H2 headings, author box at the end.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$fxt_parts = fxt_tt_content_parts();
	fxt_tt_the_page_hero(
		array(
			'class' => 'method-hero',
			'extra' => $fxt_parts['hero'],
			'after' => static function () {
				fxt_tt_the_byline();
			},
		)
	);
	?>
	<div class="container doc-layout">
		<nav class="toc" aria-label="<?php esc_attr_e( 'On this page', 'fx-trading-today' ); ?>" data-toc hidden>
			<p class="u-label toc__title"><?php esc_html_e( 'On this page', 'fx-trading-today' ); ?></p>
		</nav>
		<div class="doc-content entry-content prose">
			<?php echo $fxt_parts['body']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content. ?>
			<?php fxt_tt_the_author_box( (int) get_post_field( 'post_author' ), __( 'Written by', 'fx-trading-today' ) ); ?>
		</div>
	</div>
	<?php
endwhile;
get_footer();
