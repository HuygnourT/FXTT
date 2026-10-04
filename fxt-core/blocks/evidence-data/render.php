<?php
/**
 * Render: fxt/evidence-data.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Blocks;
use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_post_id  = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$fxt_evidence = Repository::evidence( $fxt_post_id );
$fxt_section  = in_array( $attributes['section'], array( 'summary', 'timeline', 'deposits', 'withdrawals' ), true ) ? $attributes['section'] : 'summary';

if ( ! $fxt_evidence ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<p class="empty-state">' . esc_html__( 'Evidence data appears here when this block is used inside a Test Evidence post.', 'fxt-core' ) . '</p>';
	}
	return;
}
if ( 'summary' !== $fxt_section && ! is_admin() ) {
	Blocks::queue_viewer( $fxt_evidence );
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'evidence-data evidence-data--' . $fxt_section ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	if ( in_array( $fxt_section, array( 'deposits', 'withdrawals' ), true ) ) {
		fxt_core_view( 'evidence/tests', array( 'evidence' => $fxt_evidence, 'kind' => $fxt_section ) );
	} else {
		fxt_core_view( 'evidence/' . $fxt_section, array( 'evidence' => $fxt_evidence ) );
	}
	?>
</div>
