<?php
/**
 * Trading cost calculator: estimated cost of one trade per account type and
 * volume, from the broker's account data (see FXT\Core\Costs).
 *
 * @var array $args { broker, instrument, basis, show_breakdown, show_assumptions }
 * @package FXT\Core
 */

use FXT\Core\Costs;

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
if ( ! $broker['accounts'] ) {
	echo '<p class="empty-state">' . esc_html__( 'Account types are being researched, so trading costs cannot be estimated yet.', 'fxt-core' ) . '</p>';
	return;
}

$table      = Costs::table( $broker, $args['instrument'], $args['basis'] );
$instrument = $table['instrument'];
$options    = Costs::options();
$bases      = Costs::bases();
$breakdown  = ! empty( $args['show_breakdown'] );
$spread_f   = $table['fields']['spread'];
$comm_f     = $table['fields']['commission'];
$round_trip = 'round_trip' === $table['basis'];
?>
<div class="table-scroll">
	<table class="data-table data-table--costs">
		<caption class="cost-calc__caption">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: broker name, 2: instrument, 3: trade basis */
					__( 'Estimated trading cost per trade for %1$s accounts, %2$s, %3$s', 'fxt-core' ),
					$broker['name'],
					$instrument ? $instrument['name'] : __( 'no instrument', 'fxt-core' ),
					strtolower( $bases[ $table['basis'] ] )
				)
			);
			?>
		</caption>
		<thead><tr>
			<th scope="col"><?php echo esc_html( $broker['account_display']['account_label'] ); ?></th>
			<?php foreach ( $table['volumes'] as $volume ) : ?>
				<th scope="col" class="cost-calc__num"><?php echo esc_html( Costs::volume_label( $volume ) ); ?></th>
			<?php endforeach; ?>
		</tr></thead>
		<tbody>
		<?php foreach ( $table['rows'] as $row ) : ?>
			<tr>
				<th scope="row"><?php echo fxt_core_text( $row['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<?php foreach ( $row['cells'] as $cell ) : ?>
					<td class="cost-calc__num">
						<span class="cost-calc__total<?php echo null === $cell['total'] ? ' cost-calc__na' : ''; ?>"><?php echo esc_html( Costs::money( $cell['total'] ) ); ?></span>
						<?php if ( $breakdown ) : ?>
							<span class="cost-calc__parts">
								<span><?php echo esc_html__( 'Spread', 'fxt-core' ) . ' ' . esc_html( Costs::money( $cell['spread'] ) ); ?></span>
								<span><?php echo esc_html__( 'Commission', 'fxt-core' ) . ' ' . esc_html( Costs::money( $cell['commission'] ) ); ?></span>
							</span>
						<?php endif; ?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php if ( ! empty( $args['show_assumptions'] ) ) : ?>
	<dl class="cost-calc__assumptions">
		<div>
			<dt><?php esc_html_e( 'Instrument', 'fxt-core' ); ?></dt>
			<dd>
				<?php
				if ( $instrument ) {
					echo esc_html(
						sprintf(
							/* translators: 1: instrument, 2: units per lot, 3: pip size, 4: pip value */
							__( '%1$s: 1 lot = %2$s units, 1 pip = %3$s, pip value %4$s per lot', 'fxt-core' ),
							$instrument['name'],
							number_format_i18n( $instrument['units'] ),
							rtrim( rtrim( sprintf( '%.8F', $instrument['pip_size'] ), '0' ), '.' ),
							Costs::money( $table['pip_value'] )
						)
					);
					if ( null === $table['pip_value'] ) {
						echo ' ' . esc_html__( '(no USD rate for the quote currency, so costs are N/A)', 'fxt-core' );
					}
				} else {
					esc_html_e( 'No instrument configured.', 'fxt-core' );
				}
				?>
			</dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Trade', 'fxt-core' ); ?></dt>
			<dd>
				<?php
				echo esc_html(
					$round_trip
						? __( 'Round trip: the position is opened and closed. The spread is paid once; a per-side commission is charged twice, a round-turn commission once.', 'fxt-core' )
						: __( 'Opening a position only. The spread is paid once; a per-side commission is charged once, half of a round-turn commission.', 'fxt-core' )
				);
				?>
			</dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Spread', 'fxt-core' ); ?></dt>
			<dd>
				<?php
				echo esc_html(
					$spread_f
						/* translators: %s: field label, e.g. "Spread from" */
						? sprintf( __( 'Pips from the "%s" value of each account, times the pip value and the volume.', 'fxt-core' ), $spread_f['label'] )
						: __( 'No spread data for this instrument in the account types, so spread costs are N/A.', 'fxt-core' )
				);
				?>
			</dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Commission', 'fxt-core' ); ?></dt>
			<dd>
				<?php
				echo esc_html(
					$comm_f
						/* translators: %s: field label */
						? sprintf( __( 'From the "%s" value of each account (USD per lot, per side or round turn).', 'fxt-core' ), $comm_f['label'] )
						: __( 'No commission data in the account types, so commissions are N/A.', 'fxt-core' )
				);
				?>
			</dd>
		</div>
		<?php
		$lot_notes = array();
		foreach ( $table['rows'] as $row ) {
			if ( $row['lot_units'] ) {
				/* translators: 1: account name, 2: units per lot */
				$lot_notes[] = sprintf( __( '%1$s: 1 lot = %2$s units', 'fxt-core' ), $row['name'], number_format_i18n( $row['lot_units'] ) );
			}
		}
		if ( $lot_notes ) :
			?>
			<div>
				<dt><?php esc_html_e( 'Lot size', 'fxt-core' ); ?></dt>
				<dd><?php echo esc_html( implode( '; ', $lot_notes ) . '.' ); ?></dd>
			</div>
		<?php endif; ?>
	</dl>
	<?php if ( $instrument && '' !== $instrument['note'] ) : ?>
		<p class="cost-calc__note"><?php echo esc_html( $instrument['note'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== trim( (string) $options['note'] ) ) : ?>
		<p class="cost-calc__note"><?php echo esc_html( $options['note'] ); ?></p>
	<?php endif; ?>
<?php endif; ?>
