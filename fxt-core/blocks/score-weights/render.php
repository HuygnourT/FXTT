<?php
/**
 * Render: fxt/score-weights.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/score-weights', array( 'variant' => 'table' === $attributes['variant'] ? 'table' : 'list' ) ); ?>
</div>
