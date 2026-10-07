<?php
/**
 * Render: fxt/evidence-list.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$fxt_args = Blocks::evidence_list_args( $attributes );
$fxt_wrap = array();
if ( 'country' === $fxt_args['scope'] ) {
	// Follows the visitor's country: refreshed in place when it changes.
	$fxt_wrap = array(
		'data-fxt-region' => 'evidence-list',
		'data-fxt-args'   => wp_json_encode( $fxt_args ),
	);
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
$fxt_args['filter'] = isset( $_GET['evidence_country'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['evidence_country'] ) ) ) : '';
$fxt_args['base']   = Blocks::base_url();
?>
<div <?php echo get_block_wrapper_attributes( $fxt_wrap ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/evidence-list', $fxt_args ); ?>
</div>
