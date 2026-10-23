<?php
/**
 * Trading cost calculator (1.4.4): spread cost, commission and total for
 * EUR/USD and XAU/USD, for every account type, at the lot size the visitor
 * enters.
 *
 *   Spread cost = spread cost per 1.00 lot x LOT
 *   Commission  = commission per 1.00 lot (round turn) x LOT
 *   Total       = spread cost + commission (N/A when either is missing)
 *
 * Rendered for 1.00 lot (works without JavaScript); cost-calc.js
 * recalculates when LOT changes. 0 is a valid value; empty is N/A.
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

$uid         = wp_unique_id( 'fxt-cost-calc-' );
$instruments = array(
	'eurusd' => 'EUR/USD',
	'xauusd' => 'XAU/USD',
);
$parts       = array(
	'spread' => __( 'Spread cost', 'fxt-core' ),
	'comm'   => __( 'Commission', 'fxt-core' ),
	'total'  => __( 'Total cost', 'fxt-core' ),
);
$value       = static function ( array $account, $key ) {
	return array_key_exists( $key, $account ) && null !== $account[ $key ] && '' !== $account[ $key ] ? (float) $account[ $key ] : null;
};
?>
<div class="cost-calc__box" data-fxt-cost-calc data-na="<?php esc_attr_e( 'N/A', 'fxt-core' ); ?>">
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
				printf( esc_html__( 'Spread cost, round-turn commission and total cost per trade for %1$s account types at %2$s lot', 'fxt-core' ), esc_html( $broker['name'] ), '<span data-fxt-lot-value>1.00</span>' );
				?>
			</caption>
			<thead>
				<tr>
					<th scope="col" rowspan="2" class="cost-calc__account"><?php echo esc_html( $broker['account_display']['account_label'] ); ?></th>
					<?php foreach ( $instruments as $label ) : ?>
						<th scope="colgroup" colspan="3" class="cost-calc__inst"><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
				</tr>
				<tr>
					<?php foreach ( $instruments as $label ) : ?>
						<th scope="col" class="cost-calc__num"><?php esc_html_e( 'Spread', 'fxt-core' ); ?></th>
						<th scope="col" class="cost-calc__num"><?php esc_html_e( 'Commission', 'fxt-core' ); ?> <span class="cost-calc__basis"><?php esc_html_e( 'round turn', 'fxt-core' ); ?></span></th>
						<th scope="col" class="cost-calc__num cost-calc__total-head"><?php esc_html_e( 'Total', 'fxt-core' ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $broker['accounts'] as $account ) : ?>
				<?php $unit = isset( $account['cost_unit'] ) && '' !== $account['cost_unit'] ? $account['cost_unit'] : '$'; ?>
				<tr data-fxt-cost-row>
					<th scope="row"><?php echo fxt_core_text( $account['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
					<?php
					foreach ( $instruments as $inst => $inst_label ) :
						$spread = $value( $account, 'cost_' . $inst );
						$comm   = $value( $account, 'comm_' . $inst );
						// Each part rounded to cents first, as in cost-calc.js.
						$total  = null !== $spread && null !== $comm ? round( $spread, 2 ) + round( $comm, 2 ) : null;
						$cells  = array(
							'spread' => $spread,
							'comm'   => $comm,
							'total'  => $total,
						);
						foreach ( $cells as $part => $amount ) :
							$classes = 'cost-calc__num cost-calc__' . $part . ( 'spread' === $part ? ' cost-calc__first' : '' ) . ( null === $amount ? ' cost-calc__na' : '' );
							?>
							<td class="<?php echo esc_attr( $classes ); ?>" data-inst="<?php echo esc_attr( $inst ); ?>" data-part="<?php echo esc_attr( $part ); ?>"<?php echo 'total' !== $part ? ' data-value="' . esc_attr( null === $amount ? '' : (string) $amount ) . '"' : ''; ?> data-unit="<?php echo esc_attr( $unit ); ?>" data-label="<?php echo esc_attr( $inst_label . ' ' . strtolower( $parts[ $part ] ) ); ?>"><?php echo esc_html( Accounts::money( $amount, $unit ) ); ?></td>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="cost-calc__note"><?php esc_html_e( 'Spread cost and commission = value per 1.00 lot x LOT, from the account type data; total = spread cost + commission. Commission is round turn: opening and closing the position together. N/A: a value has not been entered. Swaps and other fees are not included.', 'fxt-core' ); ?></p>
</div>
