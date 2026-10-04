<?php
/**
 * Broker data: trading costs and execution.
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$rows   = array(
	__( 'Typical spread', 'fxt-core' )  => $broker['costs']['spread'],
	__( 'Commission', 'fxt-core' )      => $broker['costs']['commission'],
	__( 'Swap', 'fxt-core' )            => $broker['costs']['swap'],
	__( 'Execution model', 'fxt-core' ) => $broker['trading']['execution'],
	__( 'Order types', 'fxt-core' )     => $broker['trading']['order_types'],
	__( 'Other fees', 'fxt-core' )      => $broker['costs']['other_fees'],
);
?>
<div class="table-scroll">
	<table class="data-table data-table--kv">
		<tbody>
		<?php foreach ( $rows as $label => $value ) : ?>
			<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><?php echo fxt_core_text( $value ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
