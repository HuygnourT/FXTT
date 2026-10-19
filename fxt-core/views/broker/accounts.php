<?php
/**
 * Broker data: account types table.
 *
 * Columns and their order come from the broker's display options (Broker
 * data: account types display options); values from its account rows. A
 * row with its own source shows its label in that row; the table source
 * (Broker data: sources > Account types) is placed where the block says.
 *
 * @var array $args { broker: array, source_position?: string, source_after?: int }
 * @package FXT\Core
 */

use FXT\Core\Accounts;
use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker   = $args['broker'];
$accounts = $broker['accounts'];
if ( ! $accounts ) {
	echo '<p class="empty-state">' . esc_html__( 'Account types are being researched.', 'fxt-core' ) . '</p>';
	return;
}
$columns  = Accounts::columns( $broker );
$source   = Repository::source( $broker, 'accounts' );
$place    = fxt_core_source_position( $args, 'above' );
$colspan  = count( $columns ) + 1;
$count    = count( $accounts );

if ( 'above' === $place['position'] ) {
	echo fxt_core_source_line( $source ); // phpcs:ignore WordPress.Security.EscapeOutput
}
?>
<div class="table-scroll">
	<table class="data-table data-table--accounts">
		<thead><tr>
			<th scope="col"><?php echo esc_html( $broker['account_display']['account_label'] ); ?></th>
			<?php foreach ( $columns as $column ) : ?>
				<th scope="col"><?php echo esc_html( $column['label'] ); ?></th>
			<?php endforeach; ?>
		</tr></thead>
		<tbody>
		<?php
		foreach ( array_values( $accounts ) as $i => $account ) :
			if ( 'after' === $place['position'] && $i === $place['after'] ) {
				echo fxt_core_source_row( $source, $colspan ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			// The row's own source wins; "In each row" shows the table source otherwise.
			$row_source = '' !== $account['source']
				? Repository::resolve_source( array( 'source' => $account['source'], 'ref' => $account['source_ref'], 'checked' => $account['source_checked'] ), $broker )
				: ( 'rows' === $place['position'] ? $source : null );
			?>
			<tr>
				<th scope="row">
					<?php echo fxt_core_text( $account['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo $row_source ? ' ' . fxt_core_source_tag( $row_source ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</th>
				<?php foreach ( $columns as $column ) : ?>
					<td<?php echo ( 'min_deposit' === $column['id'] || 'number' === $column['type'] ) ? ' class="u-mono"' : ''; ?>><?php echo fxt_core_text( Accounts::value_text( $column, $account ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<?php endforeach; ?>
			</tr>
			<?php
		endforeach;
		if ( 'after' === $place['position'] && $place['after'] >= $count ) {
			echo fxt_core_source_row( $source, $colspan ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
		</tbody>
	</table>
</div>
<?php
if ( 'below' === $place['position'] ) {
	echo fxt_core_source_line( $source ); // phpcs:ignore WordPress.Security.EscapeOutput
}
