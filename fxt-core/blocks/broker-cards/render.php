<?php
/**
 * Render: fxt/broker-cards.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo get_block_wrapper_attributes( array( 'data-fxt-region' => 'broker-cards' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/broker-cards', array( 'count' => max( 1, min( 12, (int) $attributes['count'] ) ) ) ); ?>
</div>
