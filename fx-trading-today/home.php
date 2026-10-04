<?php
/**
 * Posts page ("Latest research").
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

$fxt_page = (int) get_option( 'page_for_posts' );
get_header();
get_template_part(
	'template-parts/archive',
	null,
	array(
		'title' => $fxt_page ? get_the_title( $fxt_page ) : __( 'Latest research', 'fx-trading-today' ),
		'lead'  => $fxt_page ? get_post_field( 'post_excerpt', $fxt_page ) : '',
	)
);
get_footer();
