<?php
/**
 * Broker data: test status by country with evidence links.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$code   = $args['code'];
$codes  = Repository::tested_markets( $broker );

if ( ! $codes ) {
	/* translators: %s: broker name */
	echo '<p class="empty-state">' . esc_html( sprintf( __( 'No tests have started for %s.', 'fxt-core' ), $broker['name'] ) ) . '</p>';
	return;
}
?>
<div class="table-scroll">
	<table class="data-table">
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Country', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Deposit', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Withdrawal', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Platform', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Support', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Last tested', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Evidence', 'fxt-core' ); ?></th>
		</tr></thead>
		<tbody>
		<?php
		foreach ( $codes as $market_code ) :
			$row      = Repository::market_row( $broker, $market_code );
			$market   = Repository::market( $market_code );
			$evidence = Repository::evidence_for( $broker['id'], $market_code );
			?>
			<tr<?php echo $market_code === $code ? ' class="is-current"' : ''; ?>>
				<th scope="row"><?php echo esc_html( $market ? $market['name'] : $market_code ); ?></th>
				<?php foreach ( array( 'deposit', 'withdrawal', 'platform', 'support' ) as $key ) : ?>
					<td><?php echo fxt_core_badge( 'test', isset( $row[ $key ] ) ? (string) $row[ $key ] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<?php endforeach; ?>
				<td class="u-mono"><?php echo esc_html( fxt_core_date( isset( $row['last_tested'] ) ? $row['last_tested'] : '' ) ); ?></td>
				<td>
					<?php if ( $evidence ) : ?>
						<a class="link-strong" href="<?php echo esc_url( $evidence['url'] ); ?>"><?php esc_html_e( 'View evidence', 'fxt-core' ); ?></a>
					<?php else : ?>
						<span class="muted"><?php esc_html_e( 'Not published', 'fxt-core' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
