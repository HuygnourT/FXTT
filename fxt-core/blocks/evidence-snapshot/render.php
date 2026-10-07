<?php
/**
 * Render: fxt/evidence-snapshot.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$fxt_html = fxt_core_get_view(
	'blocks/evidence-snapshot',
	array(
		'broker'  => sanitize_title( $attributes['broker'] ),
		'country' => strtoupper( sanitize_key( $attributes['country'] ) ),
	)
);
if ( '' === trim( $fxt_html ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php echo $fxt_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the view. ?>
</div>
