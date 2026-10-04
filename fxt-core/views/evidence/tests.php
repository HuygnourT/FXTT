<?php
/**
 * Evidence data: deposit or withdrawal tests table.
 *
 * @var array $args { evidence: array, kind: 'deposits'|'withdrawals' }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$evidence    = $args['evidence'];
$withdrawals = 'withdrawals' === $args['kind'];
$rows        = $withdrawals ? $evidence['withdrawals'] : $evidence['deposits'];
if ( ! $rows ) {
	echo '<p class="empty-state">' . esc_html__( 'No tests recorded yet.', 'fxt-core' ) . '</p>';
	return;
}
$cell = static function ( $row, $key ) {
	return isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
};
?>
<div class="table-scroll">
	<table class="data-table">
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Test ID', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Date', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Method', 'fxt-core' ); ?></th>
			<?php if ( $withdrawals ) : ?>
				<th scope="col"><?php esc_html_e( 'Amount', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Requested', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Received', 'fxt-core' ); ?></th>
			<?php else : ?>
				<th scope="col"><?php esc_html_e( 'Currency', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Amount', 'fxt-core' ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php esc_html_e( 'Processing time', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Fee', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Result', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Evidence', 'fxt-core' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<?php
			$id      = $cell( $row, 'id' );
			$success = 0 === strcasecmp( $cell( $row, 'result' ), 'successful' );
			?>
			<tr>
				<th scope="row" class="u-mono"><?php echo esc_html( $id ); ?></th>
				<td class="u-mono"><?php echo esc_html( fxt_core_date( $cell( $row, 'date' ) ) ); ?></td>
				<td><?php echo esc_html( $cell( $row, 'method' ) ); ?></td>
				<?php if ( $withdrawals ) : ?>
					<td class="u-mono"><?php echo esc_html( trim( $cell( $row, 'amount' ) . ' ' . $cell( $row, 'currency' ) ) ); ?></td>
					<td class="u-mono"><?php echo esc_html( fxt_core_datetime( $cell( $row, 'requested' ) ) ); ?></td>
					<td class="u-mono"><?php echo esc_html( fxt_core_datetime( $cell( $row, 'received' ) ) ); ?></td>
				<?php else : ?>
					<td class="u-mono"><?php echo esc_html( $cell( $row, 'currency' ) ); ?></td>
					<td class="u-mono"><?php echo esc_html( $cell( $row, 'amount' ) ); ?></td>
				<?php endif; ?>
				<td class="u-mono"><?php echo esc_html( $cell( $row, 'time' ) ); ?></td>
				<td class="u-mono"><?php echo esc_html( $cell( $row, 'fee' ) ); ?></td>
				<td><span class="badge <?php echo $success ? 'badge--test-complete' : 'badge--progress'; ?>"><?php echo esc_html( $cell( $row, 'result' ) ); ?></span></td>
				<td>
					<?php if ( $id && isset( $evidence['records'][ $id ] ) ) : ?>
						<button class="btn btn--secondary btn--sm" type="button" data-view-evidence="<?php echo esc_attr( $id ); ?>">
							<?php esc_html_e( 'View evidence', 'fxt-core' ); ?><span class="u-visually-hidden"> <?php echo esc_html( $id ); ?></span>
						</button>
					<?php else : ?>
						<span class="muted"><?php esc_html_e( 'Not published', 'fxt-core' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
