<?php
/**
 * Render: fxt/broker-compare.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$fxt_state = Blocks::compare_state( $attributes );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmp-widget' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	fxt_core_view(
		'blocks/compare',
		array(
			'mode'     => $attributes['mode'],
			'slots'    => (int) $attributes['slots'],
			'selected' => $fxt_state['selected'],
			'all'      => $fxt_state['all'],
			'sync'     => ! empty( $attributes['syncUrl'] ),
			'base_url' => Blocks::base_url(),
		)
	);
	?>
</div>
