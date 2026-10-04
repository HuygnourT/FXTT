<?php
/**
 * Render: fxt/country-brokers.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$fxt_args = array( 'limit' => max( 1, min( 8, (int) $attributes['limit'] ) ) );
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'card card--raised country-hero', 'aria-label' => __( 'Brokers available in your country', 'fxt-core' ), 'data-fxt-region' => 'country-brokers', 'data-fxt-args' => wp_json_encode( $fxt_args ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/country-brokers', $fxt_args ); ?>
</aside>
