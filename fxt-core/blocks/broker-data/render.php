<?php
/**
 * Render: fxt/broker-data. Uses the current broker post unless brokerId is set.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_post_id = ! empty( $attributes['brokerId'] ) ? (int) $attributes['brokerId'] : ( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID() );
$fxt_broker  = Repository::broker( $fxt_post_id );
$fxt_allowed = array( 'quick-facts', 'regulation', 'costs', 'platforms', 'payments', 'accounts', 'evidence', 'final-score' );
$fxt_section = in_array( $attributes['section'], $fxt_allowed, true ) ? $attributes['section'] : 'quick-facts';

if ( ! $fxt_broker ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<p class="empty-state">' . esc_html__( 'Broker data appears here when this block is used inside a Broker Review.', 'fxt-core' ) . '</p>';
	}
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'broker-data broker-data--' . $fxt_section ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'broker/' . $fxt_section, array( 'broker' => $fxt_broker, 'code' => Repository::current_market() ) ); ?>
</div>
