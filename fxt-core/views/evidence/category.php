<?php
/**
 * Evidence data (1.4.4): spread, execution or customer support tests.
 *
 * A table with one row per test, then the details of rows that have a
 * description, other measurements or images. The block renders nothing
 * when the category is empty (see blocks/evidence-data/render.php).
 *
 * @var array $args { evidence: array, kind: 'spreads'|'execution'|'support' }
 * @package FXT\Core
 */

use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$kind = $args['kind'];
$map  = array(
	'spreads'   => 'spreads',
	'execution' => 'executions',
	'support'   => 'support',
);
$rows = $args['evidence'][ $map[ $kind ] ];
if ( ! $rows ) {
	return;
}

$text     = static function ( $row, $key ) {
	return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? trim( (string) $row[ $key ] ) : '';
};
$channels = Schema::support_channels();
$results  = Schema::support_results();

// Column key => [ heading, formatter ].
$columns = array(
	'spreads'   => array(
		'date'       => __( 'Date', 'fxt-core' ),
		'instrument' => __( 'Instrument', 'fxt-core' ),
		'account'    => __( 'Account', 'fxt-core' ),
		'session'    => __( 'Session / conditions', 'fxt-core' ),
		'min'        => __( 'Lowest', 'fxt-core' ),
		'avg'        => __( 'Average', 'fxt-core' ),
		'max'        => __( 'Highest', 'fxt-core' ),
		'samples'    => __( 'Samples', 'fxt-core' ),
		'status'     => __( 'Status', 'fxt-core' ),
	),
	'execution' => array(
		'date'       => __( 'Date', 'fxt-core' ),
		'instrument' => __( 'Instrument', 'fxt-core' ),
		'order_type' => __( 'Order type', 'fxt-core' ),
		'volume'     => __( 'Volume', 'fxt-core' ),
		'conditions' => __( 'Conditions', 'fxt-core' ),
		'speed'      => __( 'Execution time', 'fxt-core' ),
		'slippage'   => __( 'Slippage', 'fxt-core' ),
		'status'     => __( 'Status', 'fxt-core' ),
	),
	'support'   => array(
		'date'       => __( 'Date', 'fxt-core' ),
		'channel'    => __( 'Channel', 'fxt-core' ),
		'language'   => __( 'Language', 'fxt-core' ),
		'question'   => __( 'Question', 'fxt-core' ),
		'response'   => __( 'First response', 'fxt-core' ),
		'resolution' => __( 'Resolution', 'fxt-core' ),
		'result'     => __( 'Result', 'fxt-core' ),
	),
);
$columns = $columns[ $kind ];

// Hide columns that are empty in every row.
foreach ( array_keys( $columns ) as $key ) {
	$used = false;
	foreach ( $rows as $row ) {
		$used = $used || '' !== $text( $row, $key );
	}
	if ( ! $used ) {
		unset( $columns[ $key ] );
	}
}

$cell = static function ( $row, $key ) use ( $text, $channels, $results ) {
	$value = $text( $row, $key );
	switch ( $key ) {
		case 'date':
			return esc_html( false !== strpos( $value, 'T' ) ? fxt_core_datetime( $value ) : fxt_core_date( $value ) );
		case 'status':
			return '' === $value ? '' : fxt_core_badge( 'test', $value );
		case 'channel':
			return esc_html( isset( $channels[ $value ] ) && '' !== $value ? $channels[ $value ] : $value );
		case 'result':
			if ( '' === $value ) {
				return '';
			}
			$class = array(
				'resolved'   => 'test-complete',
				'partial'    => 'test-in-progress',
				'unresolved' => 'test-none',
			);
			return sprintf( '<span class="badge badge--%s">%s</span>', esc_attr( isset( $class[ $value ] ) ? $class[ $value ] : 'test-none' ), esc_html( isset( $results[ $value ] ) ? $results[ $value ] : $value ) );
	}
	return esc_html( $value );
};
$mono = array( 'date', 'min', 'avg', 'max', 'samples', 'volume', 'speed', 'slippage', 'response', 'resolution' );
?>
<div class="table-scroll">
	<table class="data-table data-table--evidence-<?php echo esc_attr( $kind ); ?>">
		<thead><tr>
			<th scope="col">#</th>
			<?php foreach ( $columns as $label ) : ?>
				<th scope="col"><?php echo esc_html( $label ); ?></th>
			<?php endforeach; ?>
		</tr></thead>
		<tbody>
		<?php foreach ( $rows as $i => $row ) : ?>
			<tr>
				<th scope="row" class="u-mono"><?php echo esc_html( (string) ( $i + 1 ) ); ?></th>
				<?php foreach ( array_keys( $columns ) as $key ) : ?>
					<td<?php echo in_array( $key, $mono, true ) ? ' class="u-mono"' : ''; ?>><?php echo $cell( $row, $key ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php
$details = array();
foreach ( $rows as $i => $row ) {
	$metrics = array();
	foreach ( isset( $row['metrics'] ) ? (array) $row['metrics'] : array() as $line ) {
		$parts = array_map( 'trim', explode( '|', (string) $line, 2 ) );
		if ( '' !== $parts[0] ) {
			$metrics[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
		}
	}
	$images = array_filter( array_map( 'absint', isset( $row['images'] ) ? (array) $row['images'] : array() ), 'wp_attachment_is_image' );
	$extra  = array();
	foreach ( array( 'agent' => __( 'Agent', 'fxt-core' ), 'ticket' => __( 'Ticket / chat ID', 'fxt-core' ) ) as $key => $label ) {
		if ( '' !== $text( $row, $key ) ) {
			$extra[] = array( $label, $text( $row, $key ) );
		}
	}
	$description = $text( $row, 'description' );
	if ( '' !== $description || $metrics || $images || $extra ) {
		$details[] = array( $i + 1, $row, $description, array_merge( $extra, $metrics ), $images );
	}
}
if ( ! $details ) {
	return;
}
?>
<div class="evidence-details">
	<?php foreach ( $details as $detail ) : ?>
		<?php list( $number, $row, $description, $metrics, $images ) = $detail; ?>
		<section class="evidence-detail card">
			<h3 class="evidence-detail__title">
				<?php
				$channel = $text( $row, 'channel' );
				$subject = '' !== $text( $row, 'instrument' ) ? $text( $row, 'instrument' ) : ( isset( $channels[ $channel ] ) && '' !== $channel ? $channels[ $channel ] : $channel );
				/* translators: 1: row number, 2: instrument or channel */
				echo esc_html( trim( sprintf( __( 'Test %1$d %2$s', 'fxt-core' ), $number, '' !== $subject ? '· ' . $subject : '' ) ) );
				?>
			</h3>
			<?php if ( '' !== $description ) : ?>
				<div class="evidence-detail__text"><?php echo wpautop( esc_html( $description ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
			<?php if ( $metrics ) : ?>
				<dl class="evidence-detail__metrics">
					<?php foreach ( $metrics as $metric ) : ?>
						<div><dt><?php echo esc_html( $metric[0] ); ?></dt><dd><?php echo esc_html( $metric[1] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $images ) : ?>
				<ul class="evidence-detail__images">
					<?php foreach ( $images as $image ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) wp_get_attachment_image_url( $image, 'full' ) ); ?>" target="_blank" rel="noopener">
								<?php echo wp_get_attachment_image( $image, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
								<span class="u-visually-hidden"><?php esc_html_e( '(opens the full image in a new tab)', 'fxt-core' ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
</div>
