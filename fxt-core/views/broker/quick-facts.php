<?php
/**
 * Broker data: quick facts grid.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker    = $args['broker'];
$code      = $args['code'];
$entity    = Repository::entity( $broker, $code );
$available = 0;
foreach ( Repository::researched_markets() as $fxt_market ) {
	$available += 'yes' === Repository::availability( $broker, $fxt_market['code'] ) ? 1 : 0;
}
$account_names = wp_list_pluck( $broker['accounts'], 'name' );
?>
<dl class="fact-grid">
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Regulators (group)', 'fxt-core' ); ?></dt>
		<dd><?php echo fxt_core_regulators( $broker['regulators'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?><span class="unverified"><?php esc_html_e( 'unverified', 'fxt-core' ); ?></span></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php /* translators: %s: ISO country code */ printf( esc_html__( 'Legal entity for %s', 'fxt-core' ), esc_html( $code ) ); ?></dt>
		<dd><?php echo fxt_core_text( $entity['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Minimum deposit', 'fxt-core' ); ?></dt>
		<dd><?php echo esc_html( fxt_core_min_deposit( $broker ) ); ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Platforms', 'fxt-core' ); ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', fxt_core_platform_names( $broker ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Account types', 'fxt-core' ); ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', array_filter( $account_names ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Base currencies', 'fxt-core' ); ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', (array) $broker['currencies'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Maximum leverage', 'fxt-core' ); ?></dt>
		<dd><?php echo fxt_core_text( $broker['trading']['leverage'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Country availability', 'fxt-core' ); ?></dt>
		<dd>
			<?php echo fxt_core_badge( 'availability', Repository::availability( $broker, $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="muted">
				<?php
				/* translators: 1: number of countries, 2: researched countries */
				printf( esc_html__( 'Available in %1$d of %2$d researched countries', 'fxt-core' ), (int) $available, count( Repository::researched_markets() ) );
				?>
			</span>
		</dd>
	</div>
</dl>
