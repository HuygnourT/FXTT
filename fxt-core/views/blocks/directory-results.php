<?php
/**
 * Directory results: active filter chips, broker rows, empty state and the
 * result note. The heading text is repeated (hidden) so directory.js can copy
 * it into the H2 after a refresh; count and list use the same filtered set.
 *
 * @var array $args { filters: array, base_url: string, fields?: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$filters = $args['filters'];
$code    = $filters['market'];
$market  = $code ? Repository::market( $code ) : null;
$brokers = Blocks::filter_brokers( $filters, $code );
$base    = $args['base_url'];
$fields  = isset( $args['fields'] ) ? $args['fields'] : Blocks::directory_fields();
$vars    = array(
	'count'   => number_format_i18n( count( $brokers ) ),
	'country' => $market ? $market['name'] : Repository::ui_text( 'no_country' ),
);

$active = array();
if ( '' !== $filters['q'] ) {
	$active['q'] = Repository::ui_text( 'search_chip' ) . ': ' . $filters['q'];
}
foreach ( $fields as $key => $field ) {
	$value = $filters[ $key ];
	if ( '' === $value ) {
		continue;
	}
	$active[ $key ] = $field['label'] . ': ' . ( isset( $field['options'][ $value ] ) ? $field['options'][ $value ] : $value );
}
$query = array_filter( $filters, 'strlen' );
unset( $query['sort'] );
if ( 'score' !== $filters['sort'] ) {
	$query['sort'] = $filters['sort'];
}
?>
<p class="u-visually-hidden" data-dir-count-text><?php echo esc_html( fxt_core_directory_heading( count( $brokers ) ) ); ?></p>

<?php if ( $active ) : ?>
	<div class="active-filters">
		<?php
		foreach ( $active as $key => $text ) :
			$without = array_diff_key( $query, array( $key => true ) );
			if ( 'market' === $key ) {
				unset( $without['availability'] ); // Availability needs a country.
			}
			?>
			<a class="filter-chip" href="<?php echo esc_url( add_query_arg( $without, $base ) ); ?>" data-dir-remove="<?php echo esc_attr( $key ); ?>">
				<?php echo esc_html( $text ); ?>
				<?php echo fxt_core_icon( 'close', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="u-visually-hidden"><?php echo esc_html( Repository::ui_text( 'remove_filter' ) ); ?></span>
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
			<p class="empty-state__title"><?php echo esc_html( Repository::ui_text( 'empty_title', $vars ) ); ?></p>
			<p><?php echo esc_html( Repository::ui_text( 'empty_text', $vars ) ); ?></p>
			<p><a class="btn btn--secondary btn--sm" href="<?php echo esc_url( $base ); ?>" data-dir-clear><?php echo esc_html( Repository::ui_text( 'empty_button', $vars ) ); ?></a></p>
		</div>
	<?php endif; ?>
</div>

<?php $note = Repository::ui_text( 'result_note', $vars ); ?>
<?php if ( '' !== $note ) : ?>
	<p class="results-note"><?php echo esc_html( $note ); ?></p>
<?php endif; ?>
