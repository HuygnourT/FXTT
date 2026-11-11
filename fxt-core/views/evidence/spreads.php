<?php
/**
 * Evidence data (1.4.5): spread tests by Account type -> Session -> Instrument.
 *
 *   Account type
 *     Session (date, description)
 *       Instruments and spread results, column "Evidence" (1.4.8, right
 *       after the instrument): a
 *       "View evidence" button per instrument with images, opening that
 *       instrument's images (this session only) one at a time in a dialog
 *       (assets/js/evidence-gallery.js). Images load only when opened.
 *
 * Empty values, columns, sessions and account types are not shown. The
 * block prints nothing when there are no spread tests.
 *
 * @var array $args { evidence: array }
 * @package FXT\Core
 */

use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$accounts = $args['evidence']['spreads'];
if ( ! $accounts ) {
	return;
}
$columns = array(
	'instrument' => __( 'Instrument', 'fxt-core' ),
	'min'        => __( 'Lowest', 'fxt-core' ),
	'avg'        => __( 'Average', 'fxt-core' ),
	'max'        => __( 'Highest', 'fxt-core' ),
	'samples'    => __( 'Samples', 'fxt-core' ),
	'status'     => __( 'Status', 'fxt-core' ),
	'metrics'    => __( 'Other measurements', 'fxt-core' ),
);
$mono    = array( 'min', 'avg', 'max', 'samples' );

/**
 * Images of one instrument for the evidence dialog (loaded only when opened).
 *
 * @param int[]  $ids   Attachment IDs.
 * @param string $title Dialog title, e.g. "EUR/USD, London open".
 * @return array[]
 */
$fxt_gallery = static function ( array $ids, $title ) {
	$out = array();
	foreach ( $ids as $n => $id ) {
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$large = wp_get_attachment_image_src( $id, 'large' );
		$full  = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $large || ! $full ) {
			continue;
		}
		$alt   = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$out[] = array(
			'src'     => $large[0],
			'w'       => (int) $large[1],
			'h'       => (int) $large[2],
			'srcset'  => (string) wp_get_attachment_image_srcset( $id, 'large' ),
			'full'    => $full[0],
			'alt'     => '' !== $alt ? $alt : sprintf( /* translators: 1: instrument and session, 2: image number */ __( 'Evidence for %1$s, image %2$d', 'fxt-core' ), $title, count( $out ) + 1 ),
			'caption' => trim( wp_strip_all_tags( (string) wp_get_attachment_caption( $id ) ) ),
		);
	}
	return $out;
};
$fxt_has_gallery = false;
$single  = 1 === count( $accounts );
?>
<div class="spread-tests">
	<?php foreach ( $accounts as $a_index => $account ) : ?>
		<section class="spread-account" aria-labelledby="<?php echo esc_attr( 'spread-account-' . $a_index ); ?>">
			<h3 class="spread-account__title" id="<?php echo esc_attr( 'spread-account-' . $a_index ); ?>">
				<?php
				if ( '' !== $account['account'] ) {
					/* translators: %s: account type */
					echo esc_html( sprintf( __( '%s account', 'fxt-core' ), $account['account'] ) );
				} else {
					echo esc_html( $single ? __( 'Spread measurements', 'fxt-core' ) : __( 'Account type not specified', 'fxt-core' ) );
				}
				?>
			</h3>
			<?php if ( '' !== $account['note'] ) : ?>
				<p class="spread-account__note"><?php echo esc_html( $account['note'] ); ?></p>
			<?php endif; ?>

			<?php foreach ( $account['sessions'] as $s_index => $session ) : ?>
				<?php
				$label = '' !== $session['session'] ? $session['session'] : sprintf( /* translators: %d: session number */ __( 'Session %d', 'fxt-core' ), $s_index + 1 );
				// Columns used by at least one instrument of this session.
				$used = array();
				foreach ( $columns as $key => $heading ) {
					foreach ( $session['instruments'] as $item ) {
						if ( ( 'metrics' === $key && $item['metrics'] ) || ( 'metrics' !== $key && '' !== $item[ $key ] ) ) {
							$used[ $key ] = $heading;
							break;
						}
					}
				}
				// Evidence images of each instrument (this session only).
				$galleries = array();
				foreach ( $session['instruments'] as $i_index => $item ) {
					$name                  = '' !== $item['instrument'] ? $item['instrument'] : sprintf( /* translators: %d: instrument number */ __( 'Instrument %d', 'fxt-core' ), $i_index + 1 );
					$title                 = $name . ', ' . $label;
					$galleries[ $i_index ] = array(
						'title'  => $title,
						'images' => $fxt_gallery( isset( $item['images'] ) ? (array) $item['images'] : array(), $title ),
					);
				}
				$with_images = array_filter(
					$galleries,
					static function ( $g ) {
						return (bool) $g['images'];
					}
				);
				if ( $with_images ) {
					// Right after the instrument, so the button is in view on every screen width.
					$used            = array_slice( $used, 0, 1, true ) + array( 'evidence' => __( 'Evidence', 'fxt-core' ) ) + array_slice( $used, 1, null, true );
					$fxt_has_gallery = true;
				}
				?>
				<div class="spread-session card">
					<div class="spread-session__head">
						<h4 class="spread-session__title"><?php echo esc_html( $label ); ?></h4>
						<?php if ( '' !== $session['date'] ) : ?>
							<p class="spread-session__date u-mono"><?php echo esc_html( false !== strpos( $session['date'], 'T' ) ? fxt_core_datetime( $session['date'] ) : fxt_core_date( $session['date'] ) ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $session['description'] ) : ?>
						<div class="spread-session__text"><?php echo wpautop( esc_html( $session['description'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>

					<?php if ( $session['instruments'] && $used ) : ?>
						<div class="table-scroll">
							<table class="data-table data-table--spreads">
								<thead><tr>
									<?php foreach ( $used as $key => $heading ) : ?>
										<th scope="col"<?php echo 'evidence' === $key ? ' class="spread-evidence"' : ''; ?>><?php echo esc_html( $heading ); ?></th>
									<?php endforeach; ?>
								</tr></thead>
								<tbody>
								<?php foreach ( $session['instruments'] as $i_index => $item ) : ?>
									<tr>
										<?php foreach ( array_keys( $used ) as $key ) : ?>
											<?php if ( 'evidence' === $key ) : ?>
												<td class="spread-evidence">
													<?php
													$gallery = $galleries[ $i_index ];
													if ( $gallery['images'] ) :
														?>
														<button type="button" class="btn btn--secondary btn--sm evidence-gallery__open" aria-haspopup="dialog" data-fxt-evidence-gallery="<?php echo esc_attr( wp_json_encode( $gallery ) ); ?>">
															<?php echo fxt_core_icon( 'image', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
															<span><?php esc_html_e( 'View evidence', 'fxt-core' ); ?></span>
															<span class="evidence-gallery__n">(<?php echo (int) count( $gallery['images'] ); ?>)</span>
															<span class="u-visually-hidden"><?php echo esc_html( sprintf( /* translators: 1: number of images, 2: instrument and session */ _n( '%1$d image for %2$s', '%1$d images for %2$s', count( $gallery['images'] ), 'fxt-core' ), count( $gallery['images'] ), $gallery['title'] ) ); ?></span>
														</button>
													<?php endif; ?>
												</td>
											<?php elseif ( 'instrument' === $key ) : ?>
												<th scope="row">
													<?php echo esc_html( $item['instrument'] ); ?>
												</th>
											<?php elseif ( 'status' === $key ) : ?>
												<td><?php echo '' !== $item['status'] ? fxt_core_badge( 'test', $item['status'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
											<?php elseif ( 'metrics' === $key ) : ?>
												<td>
													<?php
													$lines = array();
													foreach ( $item['metrics'] as $line ) {
														$parts   = array_map( 'trim', explode( '|', $line, 2 ) );
														$lines[] = isset( $parts[1] ) && '' !== $parts[1] ? '<span class="spread-metric"><span class="spread-metric__label">' . esc_html( $parts[0] ) . '</span> ' . esc_html( $parts[1] ) . '</span>' : '<span class="spread-metric">' . esc_html( $parts[0] ) . '</span>';
													}
													echo implode( '', $lines ); // phpcs:ignore WordPress.Security.EscapeOutput
													?>
												</td>
											<?php else : ?>
												<td<?php echo in_array( $key, $mono, true ) ? ' class="u-mono"' : ''; ?>><?php echo esc_html( $item[ $key ] ); ?></td>
											<?php endif; ?>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php // Session-level images saved before 1.4.8 are not shown: their instrument is not known (assign them in the admin). ?>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endforeach; ?>
</div>
<?php
if ( $fxt_has_gallery ) {
	wp_enqueue_script( 'fxt-core-evidence-gallery' );
}
