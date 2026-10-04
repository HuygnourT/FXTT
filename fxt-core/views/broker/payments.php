<?php
/**
 * Broker data: deposit and withdrawal methods for the current market.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker   = $args['broker'];
$code     = $args['code'];
$market   = Repository::market( $code );
$country  = $market ? $market['name'] : $code;
$row      = Repository::market_row( $broker, $code );
$tests    = $row ? $row : array();
$evidence = Repository::evidence_for( $broker['id'], $code );
$record   = $evidence ? Repository::evidence( $evidence['id'] ) : null;
$tested   = $record ? array_map( 'strtolower', wp_list_pluck( $record['deposits'], 'method' ) ) : array();
$pay      = $broker['payments'];
$local    = ! empty( $tests['local_payments'] ) ? $tests['local_payments'] : '';
/* translators: %s: minimum deposit, e.g. $10 */
$min      = sprintf( __( '%s equivalent', 'fxt-core' ), fxt_core_min_deposit( $broker ) );

$card_tested = false;
foreach ( $tested as $method ) {
	$card_tested = $card_tested || false !== strpos( $method, 'card' );
}

$rows = array();
if ( $local ) {
	$rows[] = array( $local, $min, $pay['time_local'], $market ? $market['currency'] : '', isset( $tests['deposit'] ) ? $tests['deposit'] : '' );
}
if ( $pay['cards'] ) {
	$rows[] = array( __( 'Bank card', 'fxt-core' ), $min, $pay['time_cards'], 'USD', $card_tested ? 'complete' : '' );
}
if ( $pay['ewallets'] ) {
	$rows[] = array( __( 'E-wallets', 'fxt-core' ), $min, $pay['time_ewallets'], 'USD', '' );
}
if ( $pay['crypto'] ) {
	$rows[] = array( __( 'Crypto (USDT)', 'fxt-core' ), $min, $pay['time_crypto'], 'USDT', '' );
}
if ( $pay['bank'] && ! $local ) {
	$rows[] = array( __( 'International wire', 'fxt-core' ), $min, __( '1 to 3 days', 'fxt-core' ), 'USD', isset( $tests['deposit'] ) ? $tests['deposit'] : '' );
}
?>
<p class="data-context">
	<?php echo fxt_core_icon( 'map-pin', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php
	/* translators: %s: country name */
	printf( esc_html__( 'Payment methods for clients in %s', 'fxt-core' ), '<strong>' . esc_html( $country ) . '</strong>' );
	?>
	<button class="link-button" type="button" data-open-country><?php esc_html_e( 'Change country', 'fxt-core' ); ?></button>
</p>

<?php if ( $rows ) : ?>
	<div class="table-scroll">
		<table class="data-table">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'Payment method', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Minimum amount', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Processing time', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Currency', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Test status', 'fxt-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $r[0] ); ?></th>
					<td><?php echo esc_html( $r[1] ); ?></td>
					<td class="u-mono"><?php echo fxt_core_text( $r[2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td class="u-mono"><?php echo fxt_core_text( $r[3] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td><?php echo fxt_core_badge( 'test', $r[4] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<p class="empty-state">
		<?php
		/* translators: %s: country name */
		printf( esc_html__( 'No payment methods researched for %s yet.', 'fxt-core' ), esc_html( $country ) );
		?>
	</p>
<?php endif; ?>

<?php if ( $record ) : ?>
	<a class="evidence-link" href="<?php echo esc_url( $evidence['url'] ); ?>">
		<?php echo fxt_core_icon( 'file' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>
			<strong>
				<?php
				/* translators: %s: country name */
				printf( esc_html__( 'View our %s deposit and withdrawal tests', 'fxt-core' ), esc_html( $country ) );
				?>
			</strong>
			<span>
				<?php
				/* translators: 1: number of deposits, 2: number of withdrawals */
				printf( esc_html__( '%1$d deposits and %2$d withdrawals with timestamps and redacted records', 'fxt-core' ), count( $record['deposits'] ), count( $record['withdrawals'] ) );
				?>
			</span>
		</span>
		<?php echo fxt_core_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
<?php else : ?>
	<p class="callout callout--muted">
		<?php echo fxt_core_icon( 'clock', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>
			<?php
			$statuses = \FXT\Core\Schema::test_status();
			if ( Repository::has_tests( $broker, $code ) ) {
				/* translators: 1: country, 2: deposit test status, 3: withdrawal test status */
				printf( esc_html__( 'Evidence for %1$s is not published yet. Deposit tests: %2$s. Withdrawal tests: %3$s.', 'fxt-core' ), esc_html( $country ), esc_html( strtolower( $statuses[ isset( $tests['deposit'] ) ? (string) $tests['deposit'] : '' ] ) ), esc_html( strtolower( $statuses[ isset( $tests['withdrawal'] ) ? (string) $tests['withdrawal'] : '' ] ) ) );
			} else {
				/* translators: %s: country */
				printf( esc_html__( 'Testing from %s has not started yet.', 'fxt-core' ), esc_html( $country ) );
			}
			$method_url = fxt_core_page_url( 'methodology' );
			if ( $method_url ) {
				echo ' <a href="' . esc_url( $method_url . '#evidence' ) . '">' . esc_html__( 'How we publish evidence', 'fxt-core' ) . '</a>';
			}
			?>
		</span>
	</p>
<?php endif; ?>
