<?php
/**
 * Broker data: regulatory chain for the current market + entity map.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$code    = $args['code'];
$market  = Repository::market( $code );
$entity  = Repository::entity( $broker, $code );
$country = $market ? $market['name'] : $code;

$source = Repository::entity_source( $broker, $code );

// Label, value, note, show the entity's source label.
$chain = array(
	array( __( 'Broker brand', 'fxt-core' ), $broker['name'], __( 'Marketing name', 'fxt-core' ), false ),
	array( __( 'Legal entity', 'fxt-core' ), $entity['name'], __( 'Holds your account', 'fxt-core' ), true ),
	array( __( 'Regulator', 'fxt-core' ), $entity['regulator'], __( 'Supervises the entity', 'fxt-core' ), true ),
	array( __( 'Licence', 'fxt-core' ), $entity['licence'], __( 'Register number', 'fxt-core' ), true ),
	array( __( 'Jurisdiction', 'fxt-core' ), $entity['jurisdiction'], __( 'Law that applies', 'fxt-core' ), false ),
);
// v1.4.2: the chain for the visitor's country is shown only when its entity
// data is verified; otherwise nothing (no placeholders, no unverified data).
$show_chain = Repository::entity_is_verified( $broker, $code );
?>
<?php if ( $show_chain ) : ?>
<ol class="entity-chain" aria-label="<?php /* translators: %s: country */ echo esc_attr( sprintf( __( 'Regulatory chain for %s', 'fxt-core' ), $country ) ); ?>">
	<?php foreach ( $chain as $node ) : ?>
		<li class="entity-chain__node">
			<span class="u-label"><?php echo esc_html( $node[0] ); ?></span>
			<span class="entity-chain__value"><?php echo fxt_core_text( $node[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="entity-chain__note"><?php echo esc_html( $node[2] ); ?></span>
			<?php if ( $node[3] && '' !== (string) $node[1] ) : ?>
				<?php echo fxt_core_source_tag( $source ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ol>

<?php if ( $entity['protection'] && ! Repository::is_placeholder( $entity['protection'] ) ) : ?>
	<p class="callout">
		<?php echo fxt_core_icon( 'shield-check', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>
			<?php
			/* translators: %s: client protection description */
			printf( esc_html__( 'Client protection for this entity: %s. Protections of other group entities do not automatically apply.', 'fxt-core' ), esc_html( $entity['protection'] ) );
			?>
		</span>
	</p>
<?php endif; ?>
<?php endif; // $show_chain. ?>

<?php if ( ! empty( $broker['entities'] ) ) : ?>
	<h3 class="doc-subtitle">
		<?php
		/* translators: %s: broker name */
		printf( esc_html__( 'Entity map for %s', 'fxt-core' ), esc_html( $broker['name'] ) );
		?>
	</h3>
	<div class="table-scroll">
		<table class="data-table">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'Entity', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Regulator', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Jurisdiction', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Licence number', 'fxt-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php
			foreach ( $broker['entities'] as $row ) :
				$row     = Repository::normalize_entity( $row );
				$licence = Repository::is_placeholder( $row['licence'] ) ? '' : $row['licence'];
				?>
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
<?php endif; ?>
