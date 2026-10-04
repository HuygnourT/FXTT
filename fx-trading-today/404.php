<?php
/**
 * Not found.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
fxt_tt_the_page_hero(
	array(
		'title'  => __( 'Page not found', 'fx-trading-today' ),
		'lead'   => __( 'The page may have moved, or the broker may not be in our research programme yet.', 'fx-trading-today' ),
		'crumbs' => array( array( __( 'Page not found', 'fx-trading-today' ), '' ) ),
	)
);
?>
<div class="container page-body">
	<div class="page-body__search"><?php get_search_form(); ?></div>
	<p><a class="btn btn--primary" href="<?php echo esc_url( fxt_tt_url( 'directory' ) ); ?>"><?php esc_html_e( 'Browse broker reviews', 'fx-trading-today' ); ?></a></p>
</div>
<?php
get_footer();
