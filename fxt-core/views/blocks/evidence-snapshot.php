<?php
/**
 * Evidence snapshot: entity and first test results for a broker in a country,
 * read from the broker data and the Test Evidence record (no copied values).
 *
 * @var array $args { broker: string slug, country: string ISO code }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$found  = $args['broker'] ? Repository::brokers_by_slug( array( $args['broker'] ) ) : array();
$broker = $found ? $found[0] : null;
$market = Repository::market( $args['country'] );
if ( ! $broker || ! $market ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<p class="empty-state">' . esc_html__( 'Choose a broker and a country in the block settings.', 'fxt-core' ) . '</p>';
	}
	return;
}
$index    = Repository::evidence_for( $broker['id'], $market['code'] );
$evidence = $index ? Repository::evidence( $index['id'] ) : null;
if ( $evidence ) {
	$evidence['url'] = $index['url'];
}
$entity   = Repository::entity( $broker, $market['code'] );
$row      = Repository::market_row( $broker, $market['code'] );
$tests    = Schema::test_status();
$first    = static function ( $list ) {
	return $list ? reset( $list ) : null;
};
$deposit    = $evidence ? $first( $evidence['deposits'] ) : null;
$withdrawal = $evidence ? $first( $evidence['withdrawals'] ) : null;
$entity_txt = trim( (string) preg_replace( '/\s*\(.*\)$/', '', $entity['name'] ) );
$entity_txt = implode( ', ', array_filter( array( $entity_txt, $entity['jurisdiction'] ) ) );
$result     = static function ( $test ) {
	/* translators: %s: processing time */
	return $test && ! empty( $test['time'] ) ? sprintf( __( 'Tested, %s', 'fxt-core' ), $test['time'] ) : __( 'Tested', 'fxt-core' );
};
$support = isset( $row['support'] ) ? (string) $row['support'] : '';
?>
<dl class="audience-card__sample">
	<div class="audience-card__sample-row">
		<span class="u-label u-label--on-field">
			<?php if ( $evidence ) : ?>
				<a href="<?php echo esc_url( $evidence['url'] ); ?>"><?php echo esc_html( $market['name'] . ', ' . $broker['name'] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $market['name'] . ', ' . $broker['name'] ); ?>
			<?php endif; ?>
		</span>
		<?php echo fxt_core_sample( __( 'Sample', 'fxt-core' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="audience-card__sample-row"><dt><?php esc_html_e( 'Entity', 'fxt-core' ); ?></dt><dd><?php echo esc_html( $entity_txt ? $entity_txt : __( 'Research pending', 'fxt-core' ) ); ?></dd></div>
	<?php if ( $deposit ) : ?>
		<?php /* translators: %s: payment method */ ?>
		<div class="audience-card__sample-row"><dt><?php echo esc_html( sprintf( __( '%s deposit', 'fxt-core' ), $deposit['method'] ) ); ?></dt><dd class="is-ok"><?php echo esc_html( $result( $deposit ) ); ?></dd></div>
	<?php endif; ?>
	<?php if ( $withdrawal ) : ?>
		<?php /* translators: %s: payment method */ ?>
		<div class="audience-card__sample-row"><dt><?php echo esc_html( sprintf( __( '%s withdrawal', 'fxt-core' ), $withdrawal['method'] ) ); ?></dt><dd class="is-ok"><?php echo esc_html( $result( $withdrawal ) ); ?></dd></div>
	<?php endif; ?>
	<div class="audience-card__sample-row"><dt><?php esc_html_e( 'Customer support', 'fxt-core' ); ?></dt><dd class="<?php echo 'complete' === $support ? 'is-ok' : 'is-pending'; ?>"><?php echo esc_html( 'complete' === $support ? __( 'Tested', 'fxt-core' ) : $tests[ isset( $tests[ $support ] ) ? $support : '' ] ); ?></dd></div>
</dl>
