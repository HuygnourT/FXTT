<?php
/**
 * Render: fxt/broker-directory.
 *
 * @package FXT\Core
 */

use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filters.
	fxt_core_view( 'blocks/directory', array( 'filters' => Blocks::directory_filters( $_GET ), 'base_url' => Blocks::base_url() ) );
	?>
</div>
