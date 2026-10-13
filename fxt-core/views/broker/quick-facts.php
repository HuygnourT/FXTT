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
$market    = Repository::market( $code );
$available = Repository::accepting_count( $broker );
$account_names = wp_list_pluck( $broker['accounts'], 'name' );
$tag           = 'fxt_core_source_tag';
?>
<dl class="fact-grid">
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Regulators (group)', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'regulators' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo fxt_core_regulators( $broker['regulators'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?></dd>
	</div>
	<?php if ( Repository::is_tested_resident( $broker, $code ) ) : // Country-specific entity data only for Tested residents. ?>
		<div class="fact-grid__item">
			<dt><?php /* translators: %s: country name */ printf( esc_html__( 'Legal entity for %s', 'fxt-core' ), esc_html( $market ? $market['name'] : $code ) ); ?> <?php echo $tag( Repository::entity_source( $broker, $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
			<dd><?php echo fxt_core_text( $entity['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
		</div>
	<?php endif; ?>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Minimum deposit', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'min_deposit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo esc_html( fxt_core_min_deposit( $broker ) ); ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Platforms', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'platforms' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', fxt_core_platform_names( $broker ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Account types', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'accounts' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', array_filter( $account_names ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Base currencies', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'currencies' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo fxt_core_text( implode( ', ', (array) $broker['currencies'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Maximum leverage', 'fxt-core' ); ?> <?php echo $tag( Repository::source( $broker, 'leverage' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd><?php echo fxt_core_text( $broker['trading']['leverage'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
	</div>
	<div class="fact-grid__item">
		<dt><?php esc_html_e( 'Country availability', 'fxt-core' ); ?> <?php echo $tag( Repository::availability_source( $broker, $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
		<dd>
			<?php echo fxt_core_badge( 'availability', Repository::availability( $broker, $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="muted">
				<?php
				if ( empty( $broker['accepting']['configured'] ) ) {
					esc_html_e( 'Accepted countries not confirmed yet', 'fxt-core' );
				} else {
					/* translators: %s: number of countries whose residents are accepted */
					printf( esc_html( _n( 'Available in %s country', 'Available in %s countries', $available, 'fxt-core' ) ), esc_html( number_format_i18n( $available ) ) );
				}
				?>
			</span>
		</dd>
	</div>
</dl>
