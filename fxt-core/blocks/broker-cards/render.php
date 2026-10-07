<?php
/**
 * Render: fxt/broker-cards.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$fxt_args = Blocks::card_args( $attributes );
?>
<div <?php echo get_block_wrapper_attributes( array( 'data-fxt-region' => 'broker-cards', 'data-fxt-args' => wp_json_encode( $fxt_args ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/broker-cards', $fxt_args ); ?>
</div>
