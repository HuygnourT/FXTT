<?php
/**
 * Render: fxt/affiliate-cta (1.4.8).
 *
 * A button to the affiliate URL of the broker of this review (or of the
 * broker chosen in the block settings). The address always comes from the
 * broker's "Affiliate URL" field: the block has no address of its own, so
 * every button of a broker changes when that field changes. The link has
 * rel="sponsored nofollow" (plus noopener in a new tab). Without an
 * affiliate URL nothing is output (the editor shows a note instead).
 *
 * Label: the block's own text, else the broker's "Affiliate button label",
 * else "Visit {broker name}". {broker} is replaced with the broker name.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Content_Types;
use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;
$fxt_post_id = ! empty( $attributes['brokerId'] ) ? (int) $attributes['brokerId'] : ( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID() );
$fxt_broker  = Content_Types::BROKER === get_post_type( $fxt_post_id ) ? Repository::broker( $fxt_post_id ) : null;

if ( ! $fxt_broker || '' === trim( (string) $fxt_broker['affiliate_url'] ) || ! wp_http_validate_url( $fxt_broker['affiliate_url'] ) && ! preg_match( '#^https?://#i', (string) $fxt_broker['affiliate_url'] ) ) {
	if ( $fxt_preview || is_admin() ) {
		echo '<p class="empty-state">' . esc_html(
			$fxt_broker
				/* translators: %s: broker name */
				? sprintf( __( 'No affiliate URL for %s yet (Broker data: research summary > Affiliate URL), so the button is not shown on the site.', 'fxt-core' ), $fxt_broker['name'] )
				: __( 'The affiliate button appears when this block is used inside a Broker Review (or a broker is chosen in the block settings).', 'fxt-core' )
		) . '</p>';
	}
	return;
}

$fxt_label = trim( wp_strip_all_tags( (string) $attributes['label'] ) );
$fxt_label = '' !== $fxt_label ? str_replace( '{broker}', $fxt_broker['name'], $fxt_label ) : fxt_core_cta_label( $fxt_broker );
$fxt_tab   = ! isset( $attributes['newTab'] ) || ! empty( $attributes['newTab'] );
$fxt_style = isset( $attributes['style'] ) && 'secondary' === $attributes['style'] ? 'secondary' : 'primary';
$fxt_just  = isset( $attributes['justify'] ) && in_array( $attributes['justify'], array( 'left', 'center', 'right', 'full' ), true ) ? $attributes['justify'] : 'left';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'affiliate-cta affiliate-cta--' . $fxt_just ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<a class="btn btn--<?php echo esc_attr( $fxt_style ); ?><?php echo 'full' === $fxt_just ? ' btn--block' : ''; ?> affiliate-cta__link" href="<?php echo esc_url( $fxt_broker['affiliate_url'] ); ?>" rel="<?php echo esc_attr( $fxt_tab ? 'sponsored nofollow noopener' : 'sponsored nofollow' ); ?>"<?php echo $fxt_tab ? ' target="_blank"' : ''; ?>>
		<?php echo esc_html( $fxt_label ); ?>
		<?php if ( $fxt_tab ) : ?>
			<?php echo fxt_core_icon( 'external', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="u-visually-hidden"><?php esc_html_e( '(opens in a new tab)', 'fxt-core' ); ?></span>
		<?php endif; ?>
	</a>
</div>
