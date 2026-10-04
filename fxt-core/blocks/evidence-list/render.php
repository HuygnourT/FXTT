<?php
/**
 * Render: fxt/evidence-list.
 *
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/evidence-list' ); ?>
</div>
