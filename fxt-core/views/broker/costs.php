<?php
/**
 * Broker data: trading costs and execution.
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
// Source key => [label, value].
$rows   = array(
	'spread'      => array( __( 'Typical spread', 'fxt-core' ), $broker['costs']['spread'] ),
	'commission'  => array( __( 'Commission', 'fxt-core' ), $broker['costs']['commission'] ),
	'swap'        => array( __( 'Swap', 'fxt-core' ), $broker['costs']['swap'] ),
	'execution'   => array( __( 'Execution model', 'fxt-core' ), $broker['trading']['execution'] ),
	'order_types' => array( __( 'Order types', 'fxt-core' ), $broker['trading']['order_types'] ),
	'other_fees'  => array( __( 'Other fees', 'fxt-core' ), $broker['costs']['other_fees'] ),
);
// Each fact has its own source, so its label always stays in its row
// (Source label position does not apply to this section).
?>
<div class="table-scroll">
	<table class="data-table data-table--kv">
		<tbody>
		<?php foreach ( $rows as $key => $row ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
				<td><?php echo fxt_core_text( $row[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo fxt_core_source_tag( \FXT\Core\Repository::source( $broker, $key ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
