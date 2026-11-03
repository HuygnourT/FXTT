<?php
/**
 * IDs of H2 headings (1.4.6).
 *
 * Editors set an H2's ID in the block sidebar ("Heading ID", or Advanced >
 * HTML anchor; both are the Heading block's anchor). H2s left without one get
 * an ID made from their text when the page is built, so every H2 of the
 * content has a stable ID in the HTML (links to #id, table of contents),
 * unique on the page. IDs that were set are never changed.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * ID made from heading text: "Trading costs & execution" -> "trading-costs-execution".
 * Same rule as the editor suggestion and assets/js/toc.js.
 *
 * @param string $text Heading text (HTML allowed).
 * @return string
 */
function fxt_tt_heading_slug( $text ) {
	$text = remove_accents( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $text ) ), '-' );
	return '' !== $slug ? $slug : 'section';
}

/**
 * Add an ID to every H2 of the content that has none.
 *
 * @param string $content Post content (blocks already rendered).
 * @return string
 */
function fxt_tt_heading_ids( $content ) {
	if ( false === stripos( $content, '<h2' ) ) {
		return $content;
	}
	// IDs already on the page (set by editors or by blocks) stay reserved.
	static $used = array();
	if ( preg_match_all( '/\sid\s*=\s*(["\'])(.*?)\1/i', $content, $m ) ) {
		foreach ( $m[2] as $id ) {
			$used[ $id ] = true;
		}
	}
	return preg_replace_callback(
		'#<h2\b([^>]*)>(.*?)</h2>#is',
		static function ( $h ) use ( &$used ) {
			if ( preg_match( '/\sid\s*=/i', $h[1] ) ) {
				return $h[0];
			}
			$base = fxt_tt_heading_slug( $h[2] );
			$id   = $base;
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$used[ $id ] = true;
			return '<h2' . $h[1] . ' id="' . esc_attr( $id ) . '">' . $h[2] . '</h2>';
		},
		$content
	);
}
add_filter( 'the_content', 'fxt_tt_heading_ids', 20 );
