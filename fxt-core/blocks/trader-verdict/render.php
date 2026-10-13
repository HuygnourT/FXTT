<?php
/**
 * Render: fxt/trader-verdict.
 *
 * Default: the verdict written in the block (inner blocks, unchanged).
 * For the visitor's country: the verdict of the broker's country row, or the
 * "not accepting residents" verdict from Settings. The replacement reuses
 * the opening tag of the editor's quote so it keeps the same style; the
 * "Most relevant for" line is kept only while residents are accepted.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Rendered inner blocks.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_broker = fxt_core_review_broker();
$fxt_code   = Repository::current_market();
$fxt_text   = $fxt_broker ? Repository::country_verdict( $fxt_broker, $fxt_code ) : '';
if ( '' === trim( $fxt_text ) ) {
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- rendered inner blocks.
	return;
}

$fxt_open = preg_match( '/<blockquote\b[^>]*>/i', $content, $fxt_m ) ? $fxt_m[0] : '<blockquote class="wp-block-quote is-style-verdict">';
$fxt_cite = '';
if ( 'restricted' !== Repository::eligibility( $fxt_broker, $fxt_code ) && preg_match( '#<cite\b[^>]*>.*?</cite>#is', $content, $fxt_c ) ) {
	$fxt_cite = wp_kses_post( $fxt_c[0] );
}
$fxt_vars  = fxt_core_context_vars( $fxt_broker, $fxt_code );
$fxt_paras = array_filter( array_map( 'trim', preg_split( '/\R{2,}/', $fxt_text ) ), 'strlen' );

echo $fxt_open; // phpcs:ignore WordPress.Security.EscapeOutput -- tag taken from saved block content.
foreach ( $fxt_paras as $fxt_para ) {
	echo '<p>' . nl2br( fxt_core_fill( $fxt_para, $fxt_vars ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
echo $fxt_cite; // phpcs:ignore WordPress.Security.EscapeOutput
echo '</blockquote>';
