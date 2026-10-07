<?php
/**
 * Render: fxt/country-brokers.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$fxt_args = \FXT\Core\Blocks::hero_args( $attributes );
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'card card--raised country-hero', 'aria-label' => __( 'Brokers available in your country', 'fxt-core' ), 'data-fxt-region' => 'country-brokers', 'data-fxt-args' => wp_json_encode( $fxt_args ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php fxt_core_view( 'blocks/country-brokers', $fxt_args ); ?>
</aside>
