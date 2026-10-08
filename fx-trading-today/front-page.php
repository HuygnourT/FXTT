<?php
/**
 * Front page. With a static front page the content is a stack of full-width
 * sections built from patterns; otherwise fall back to the posts list.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

if ( 'page' !== get_option( 'show_on_front' ) ) {
	require get_home_template();
	return;
}

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<?php
	// Fallback heading only when the page content has no H1 of its own
	// (the home hero pattern provides one). Avoids two H1s on the page.
	if ( ! preg_match( '/<h1[\s>]/i', get_the_content() ) ) :
		?>
		<h1 class="u-visually-hidden"><?php bloginfo( 'name' ); ?></h1>
		<?php
	endif;
	?>
	<div class="entry-content entry-content--landing has-global-padding is-layout-constrained">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
