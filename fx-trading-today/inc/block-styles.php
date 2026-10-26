<?php
/**
 * Block style variations. Editors pick these in the block sidebar; the CSS
 * lives in assets/css/blocks.css. Styles map to the prototype's components so
 * content stays plain core blocks.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register block styles.
 */
function fxt_tt_block_styles() {
	$styles = array(
		'core/paragraph' => array(
			'lead'          => __( 'Lead', 'fx-trading-today' ),
			'eyebrow'       => __( 'Eyebrow (accent)', 'fx-trading-today' ),
			'eyebrow-muted' => __( 'Eyebrow (muted)', 'fx-trading-today' ),
			'note'          => __( 'Small note', 'fx-trading-today' ),
			'sample'        => __( 'Sample data tag', 'fx-trading-today' ),
			'link-arrow'    => __( 'Strong link', 'fx-trading-today' ),
			'link-card'     => __( 'Link card', 'fx-trading-today' ),
		),
		'core/list'      => array(
			'check' => __( 'Check marks', 'fx-trading-today' ),
			'lock'  => __( 'Lock marks (masked data)', 'fx-trading-today' ),
			'pros'  => __( 'Pros', 'fx-trading-today' ),
			'cons'  => __( 'Cons', 'fx-trading-today' ),
			'notes' => __( 'Researcher notes', 'fx-trading-today' ),
			'chips' => __( 'Chips', 'fx-trading-today' ),
			'risk'     => __( 'Risk list', 'fx-trading-today' ),
			'numbered' => __( 'Numbered list', 'fx-trading-today' ),
		),
		'core/table'     => array(
			'data' => __( 'Data table', 'fx-trading-today' ),
		),
		'core/group'     => array(
			'card'    => __( 'Card', 'fx-trading-today' ),
			'callout' => __( 'Callout', 'fx-trading-today' ),
		),
		'core/quote'     => array(
			'verdict' => __( 'Verdict', 'fx-trading-today' ),
		),
		'core/columns'   => array(
			'pros-cons' => __( 'Pros and cons', 'fx-trading-today' ),
		),
		'core/button'    => array(
			'secondary' => __( 'Secondary', 'fx-trading-today' ),
			'light'     => __( 'Light (on dark)', 'fx-trading-today' ),
		),
		'core/search'    => array(
			'field' => __( 'Search field', 'fx-trading-today' ),
		),
	);

	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'fxt_tt_block_styles' );

/**
 * "Numbered list" style: always an ordered list on the frontend (1.4.5).
 * Lists saved as <ul> with this style (before 1.4.5) are output as <ol>,
 * nested lists included; <li> items are unchanged.
 *
 * @param string $html  Block HTML.
 * @param array  $block Parsed block.
 * @return string
 */
function fxt_tt_numbered_list_markup( $html, $block ) {
	$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
	if ( ! preg_match( '/(^|\s)is-style-numbered(\s|$)/', $class ) || false === strpos( $html, '<ul' ) ) {
		return $html;
	}
	return preg_replace( array( '#<ul(\s|>)#', '#</ul>#' ), array( '<ol$1', '</ol>' ), $html );
}
add_filter( 'render_block_core/list', 'fxt_tt_numbered_list_markup', 10, 2 );
