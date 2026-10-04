<?php
/**
 * Directory results: count, active filter chips and broker rows.
 *
 * @var array $args { filters: array, base_url: string, fields?: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$filters = $args['filters'];
$code    = Repository::current_market();
$market  = Repository::market( $code );
$brokers = Blocks::filter_brokers( $filters, $code );
$base    = $args['base_url'];
$fields  = isset( $args['fields'] ) ? $args['fields'] : Blocks::directory_fields();
$labels  = array(
	'q'            => __( 'Search', 'fxt-core' ),
	'availability' => __( 'Availability', 'fxt-core' ),
	'regulator'    => __( 'Regulation', 'fxt-core' ),
	'platform'     => __( 'Platform', 'fxt-core' ),
	'deposit'      => __( 'Minimum deposit', 'fxt-core' ),
	'account'      => __( 'Account type', 'fxt-core' ),
	'status'       => __( 'Research status', 'fxt-core' ),
);
$active = array();
foreach ( $labels as $key => $label ) {
	if ( '' !== $filters[ $key ] ) {
		$value = $filters[ $key ];
		if ( isset( $fields[ $key ][2][ $value ] ) ) {
			$value = $fields[ $key ][2][ $value ];
		}
		$active[ $key ] = $label . ': ' . $value;
	}
}
$query = array_filter( $filters, 'strlen' );
?>
<p class="u-visually-hidden" data-dir-count-text>
	<?php
	/* translators: 1: number of brokers, 2: country */
	echo esc_html( sprintf( _n( '%1$d broker for %2$s', '%1$d brokers for %2$s', count( $brokers ), 'fxt-core' ), count( $brokers ), $market ? $market['name'] : $code ) );
	?>
</p>

<?php if ( $active ) : ?>
	<div class="active-filters">
		<?php foreach ( $active as $key => $text ) : ?>
			<a class="filter-chip" href="<?php echo esc_url( add_query_arg( array_diff_key( $query, array( $key => true ) ), $base ) ); ?>" data-dir-remove="<?php echo esc_attr( $key ); ?>">
				<?php echo esc_html( $text ); ?>
				<?php echo fxt_core_icon( 'close', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="u-visually-hidden"><?php esc_html_e( 'Remove filter', 'fxt-core' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="review-list">
	<?php if ( $brokers ) : ?>
		<?php
		foreach ( $brokers as $broker ) {
			fxt_core_view( 'cards/review-row', array( 'broker' => $broker, 'code' => $code ) );
		}
		?>
	<?php else : ?>
		<div class="empty-state">
			<p class="empty-state__title"><?php esc_html_e( 'No brokers match these filters.', 'fxt-core' ); ?></p>
			<p><?php esc_html_e( 'Try removing a filter, or choose another country. Some markets still have few verified brokers.', 'fxt-core' ); ?></p>
			<p><a class="btn btn--secondary btn--sm" href="<?php echo esc_url( $base ); ?>" data-dir-clear><?php esc_html_e( 'Clear all filters', 'fxt-core' ); ?></a></p>
		</div>
	<?php endif; ?>
</div>
