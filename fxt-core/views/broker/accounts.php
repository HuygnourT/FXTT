<?php
/**
 * Broker data: account types table.
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$accounts = $args['broker']['accounts'];
if ( ! $accounts ) {
	echo '<p class="empty-state">' . esc_html__( 'Account types are being researched.', 'fxt-core' ) . '</p>';
	return;
}
$cols = array(
	'min_deposit' => __( 'Min deposit', 'fxt-core' ),
	'spread'      => __( 'Spread from', 'fxt-core' ),
	'commission'  => __( 'Commission', 'fxt-core' ),
	'execution'   => __( 'Execution', 'fxt-core' ),
	'best_for'    => __( 'Most relevant for', 'fxt-core' ),
);
?>
<div class="table-scroll">
	<table class="data-table">
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Account', 'fxt-core' ); ?></th>
			<?php foreach ( $cols as $label ) : ?>
				<th scope="col"><?php echo esc_html( $label ); ?></th>
			<?php endforeach; ?>
		</tr></thead>
		<tbody>
		<?php foreach ( $accounts as $account ) : ?>
			<tr>
				<th scope="row"><?php echo fxt_core_text( isset( $account['name'] ) ? $account['name'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<?php foreach ( array_keys( $cols ) as $key ) : ?>
					<td<?php echo 'min_deposit' === $key ? ' class="u-mono"' : ''; ?>><?php echo fxt_core_text( isset( $account[ $key ] ) ? $account[ $key ] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
