<?php
/**
 * Trading cost calculator (v1.4.3): spread cost of EUR/USD and XAU/USD for
 * every account type at the lot size the visitor enters.
 *
 *   Total cost = cost per 1.00 lot (Broker data: account types) x LOT
 *
 * The table is rendered for 1.00 lot (works without JavaScript);
 * cost-calc.js recalculates every row when LOT changes. An empty cost is
 * N/A; 0 is a valid cost.
 *
 * @var array $args { broker: array, title?: string }
 * @package FXT\Core
 */

use FXT\Core\Accounts;

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$title  = isset( $args['title'] ) ? trim( (string) $args['title'] ) : '';
if ( ! $broker['accounts'] ) {
	echo '<p class="empty-state">' . esc_html__( 'Account types are being researched, so trading costs cannot be calculated yet.', 'fxt-core' ) . '</p>';
	return;
}

$uid     = wp_unique_id( 'fxt-cost-calc-' );
$columns = array(
	'cost_eurusd' => __( 'EUR/USD Spread Cost', 'fxt-core' ),
	'cost_xauusd' => __( 'XAU/USD Spread Cost', 'fxt-core' ),
);
$na      = __( 'N/A', 'fxt-core' );
?>
<div class="cost-calc__box" data-fxt-cost-calc data-na="<?php echo esc_attr( $na ); ?>">
	<?php if ( '' !== $title ) : ?>
		<p class="cost-calc__title"><?php echo esc_html( $title ); ?></p>
	<?php endif; ?>
	<div class="cost-calc__lot">
		<label class="cost-calc__lot-label" for="<?php echo esc_attr( $uid ); ?>-lot"><?php esc_html_e( 'LOT', 'fxt-core' ); ?></label>
		<input class="input cost-calc__lot-input" type="number" id="<?php echo esc_attr( $uid ); ?>-lot" value="1.00" min="0.01" step="0.01" inputmode="decimal" data-fxt-lot aria-describedby="<?php echo esc_attr( $uid ); ?>-help">
		<p class="cost-calc__help" id="<?php echo esc_attr( $uid ); ?>-help" data-fxt-lot-help data-error="<?php esc_attr_e( 'Enter a lot size of 0.01 or more, in steps of 0.01.', 'fxt-core' ); ?>"><?php esc_html_e( 'Minimum 0.01 lot, in steps of 0.01.', 'fxt-core' ); ?></p>
	</div>
	<div class="table-scroll">
		<table class="data-table data-table--costs">
			<caption class="u-visually-hidden" aria-live="polite">
				<?php
				/* translators: 1: broker name, 2: lot size */
				printf( esc_html__( 'Spread cost per trade for %1$s account types at %2$s lot', 'fxt-core' ), esc_html( $broker['name'] ), '<span data-fxt-lot-value>1.00</span>' );
				?>
			</caption>
			<thead><tr>
				<th scope="col"><?php echo esc_html( $broker['account_display']['account_label'] ); ?></th>
				<?php foreach ( $columns as $label ) : ?>
					<th scope="col" class="cost-calc__num"><?php echo esc_html( $label ); ?></th>
				<?php endforeach; ?>
			</tr></thead>
			<tbody>
			<?php foreach ( $broker['accounts'] as $account ) : ?>
				<?php $unit = isset( $account['cost_unit'] ) && '' !== $account['cost_unit'] ? $account['cost_unit'] : '$'; ?>
				<tr>
					<th scope="row"><?php echo fxt_core_text( $account['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
					<?php
					foreach ( array_keys( $columns ) as $key ) :
						$cost = array_key_exists( $key, $account ) && null !== $account[ $key ] && '' !== $account[ $key ] ? (float) $account[ $key ] : null;
						?>
						<td class="cost-calc__num<?php echo null === $cost ? ' cost-calc__na' : ''; ?>" data-cost="<?php echo null === $cost ? '' : esc_attr( (string) $cost ); ?>" data-unit="<?php echo esc_attr( $unit ); ?>"><?php echo esc_html( Accounts::money( $cost, $unit ) ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="cost-calc__note"><?php esc_html_e( 'Cost = cost per 1.00 lot x LOT, from the spread cost entered for each account type. N/A: no cost entered for that instrument. Swaps, commissions charged separately and other fees are not included unless they are part of the cost entered.', 'fxt-core' ); ?></p>
</div>
