<?php
/**
 * Search results.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part(
	'template-parts/archive',
	null,
	array(
		/* translators: %s: search query */
		'title'  => sprintf( __( 'Results for "%s"', 'fx-trading-today' ), get_search_query() ),
		'lead'   => '',
		'search' => true,
	)
);
get_footer();
