<?php
/**
 * Broker CTAs: compare (primary) and the labelled affiliate link (secondary).
 *
 * @var array $args { broker: array, show_affiliate?: bool }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$others  = array();
foreach ( Repository::brokers() as $other ) {
	if ( $other['id'] !== $broker['id'] && null !== $other['score'] && count( $others ) < 2 ) {
		$others[] = $other['slug'];
	}
}
$compare = fxt_core_compare_url( array_merge( array( $broker['slug'] ), $others ) );
?>
<div class="review-hero__actions">
	<?php if ( $compare ) : ?>
		<a class="btn btn--primary" href="<?php echo esc_url( $compare ); ?>">
			<?php
			/* translators: %s: broker name */
			printf( esc_html__( 'Compare %s with other brokers', 'fxt-core' ), esc_html( $broker['name'] ) );
			?>
		</a>
	<?php endif; ?>
	<?php if ( $broker['affiliate_url'] && ( ! isset( $args['show_affiliate'] ) || $args['show_affiliate'] ) ) : ?>
		<a class="btn btn--secondary" href="<?php echo esc_url( $broker['affiliate_url'] ); ?>" rel="sponsored nofollow noopener" target="_blank">
			<?php
			/* translators: %s: broker name */
			$cta_label = $broker['cta_label'] ? $broker['cta_label'] : sprintf( __( 'Visit %s', 'fxt-core' ), $broker['name'] );
			/* translators: %s: button label */
			echo esc_html( sprintf( __( '%s (affiliate link)', 'fxt-core' ), $cta_label ) );
			?>
			<?php echo fxt_core_icon( 'external', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="u-visually-hidden"><?php esc_html_e( '(opens in a new tab)', 'fxt-core' ); ?></span>
		</a>
	<?php endif; ?>
</div>
