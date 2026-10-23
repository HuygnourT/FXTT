<?php
/**
 * Broker data: Regulation and entities.
 *
 * One table: "Entity map for {Broker name}" listing every legal entity of
 * the broker (entity, regulator with its source label, jurisdiction,
 * licence number), whatever the visitor's country. Placeholder licences
 * (TBD, N/A...) show "Not available". The client protection note of the
 * entity serving the visitor's country is shown under the table when that
 * country is a Tested resident with verified entity data.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker   = $args['broker'];
$code     = strtoupper( (string) $args['code'] );
$entities = array_values( array_filter( array_map( array( Repository::class, 'normalize_entity' ), (array) $broker['entities'] ), static function ( $row ) {
	return '' !== trim( $row['name'] . $row['regulator'] . $row['jurisdiction'] . $row['licence'] );
} ) );
if ( ! $entities ) {
	return;
}
?>
<h3 class="doc-subtitle">
	<?php
	/* translators: %s: broker name */
	printf( esc_html__( 'Entity map for %s', 'fxt-core' ), esc_html( $broker['name'] ) );
	?>
</h3>
<div class="table-scroll">
	<table class="data-table data-table--entities">
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Entity', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Regulator', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Jurisdiction', 'fxt-core' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Licence number', 'fxt-core' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $entities as $row ) : ?>
			<?php $licence = Repository::is_placeholder( $row['licence'] ) ? '' : $row['licence']; ?>
			<tr>
				<th scope="row"><?php echo fxt_core_text( $row['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
				<td><?php echo fxt_core_text( $row['regulator'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo '' !== $row['regulator'] ? fxt_core_source_tag( Repository::resolve_source( array( 'source' => $row['source'], 'ref' => $row['source_ref'], 'checked' => $row['checked'] ), $broker ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td><?php echo fxt_core_text( $row['jurisdiction'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td class="u-mono"><?php echo '' !== $licence ? esc_html( $licence ) : '<span class="muted">' . esc_html__( 'Not available', 'fxt-core' ) . '</span>'; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php
if ( Repository::entity_is_verified( $broker, $code ) ) :
	$entity = Repository::entity( $broker, $code );
	if ( $entity['protection'] && ! Repository::is_placeholder( $entity['protection'] ) ) :
		?>
		<p class="callout">
			<?php echo fxt_core_icon( 'shield-check', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span>
				<?php
				/* translators: 1: entity name, 2: client protection description */
				printf( esc_html__( 'Client protection for %1$s: %2$s. Protections of other group entities do not automatically apply.', 'fxt-core' ), esc_html( $entity['name'] ), esc_html( $entity['protection'] ) );
				?>
			</span>
		</p>
		<?php
	endif;
endif;
