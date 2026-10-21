<?php
/**
 * Render: fxt/cost-calculator. Uses the current broker post unless brokerId is set.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_post_id = ! empty( $attributes['brokerId'] ) ? (int) $attributes['brokerId'] : ( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID() );
$fxt_broker  = Repository::broker( $fxt_post_id );

if ( ! $fxt_broker ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<p class="empty-state">' . esc_html__( 'The cost calculator appears here when this block is used inside a Broker Review (or a broker is chosen in the block settings).', 'fxt-core' ) . '</p>';
	}
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cost-calc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	fxt_core_view(
		'broker/cost-calculator',
		array(
			'broker' => $fxt_broker,
			'title'  => isset( $attributes['title'] ) ? (string) $attributes['title'] : '',
		)
	);
	?>
</div>
