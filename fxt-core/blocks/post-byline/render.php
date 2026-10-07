<?php
/**
 * Render: fxt/post-byline.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_post = get_post( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID() );
if ( ! $fxt_post ) {
	return;
}
$fxt_author = Repository::author( (int) $fxt_post->post_author );
$fxt_parts  = array();
if ( $fxt_author ) {
	$fxt_name = '<a href="' . esc_url( $fxt_author['url'] ) . '" rel="author">' . esc_html( $fxt_author['name'] ) . '</a>';
	if ( ! empty( $attributes['showRole'] ) && $fxt_author['role'] ) {
		$fxt_name .= ', ' . esc_html( $fxt_author['role'] );
	}
	$fxt_parts[] = '<span>' . $fxt_name . '</span>';
}
$fxt_date    = get_the_modified_date( 'Y-m-d', $fxt_post );
$fxt_time    = '<time datetime="' . esc_attr( $fxt_date ) . '">' . esc_html( get_the_modified_date( 'd M Y', $fxt_post ) ) . '</time>';
$fxt_parts[] = '' !== $attributes['dateLabel'] ? '<span>' . esc_html( $attributes['dateLabel'] ) . ' ' . $fxt_time . '</span>' : $fxt_time;
if ( 'none' !== $attributes['readTime'] ) {
	$fxt_minutes = fxt_core_reading_time( $fxt_post );
	$fxt_parts[] = '<span class="u-mono">' . esc_html(
		'short' === $attributes['readTime']
			/* translators: %d: minutes */
			? sprintf( _n( '%d min', '%d min', $fxt_minutes, 'fxt-core' ), $fxt_minutes )
			/* translators: %d: minutes */
			: sprintf( _n( '%d min read', '%d min read', $fxt_minutes, 'fxt-core' ), $fxt_minutes )
	) . '</span>';
}
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'byline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo implode( '', $fxt_parts ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></p>
