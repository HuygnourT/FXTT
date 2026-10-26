<?php
/**
 * Evidence data (1.4.5): spread tests by Account type -> Session -> Instrument.
 *
 *   Account type
 *     Session (date, description)
 *       Instruments and spread results
 *       Evidence images of this session
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
									<?php foreach ( $used as $heading ) : ?>
										<th scope="col"><?php echo esc_html( $heading ); ?></th>
									<?php endforeach; ?>
								</tr></thead>
								<tbody>
								<?php foreach ( $session['instruments'] as $item ) : ?>
									<tr>
										<?php foreach ( array_keys( $used ) as $key ) : ?>
											<?php if ( 'instrument' === $key ) : ?>
												<th scope="row"><?php echo esc_html( $item['instrument'] ); ?></th>
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

					<?php
					$images = array_values( array_filter( $session['images'], 'wp_attachment_is_image' ) );
					if ( $images ) :
						?>
						<div class="spread-session__images">
							<p class="u-label"><?php esc_html_e( 'Evidence images', 'fxt-core' ); ?></p>
							<ul class="evidence-detail__images">
								<?php foreach ( $images as $image ) : ?>
									<li>
										<a href="<?php echo esc_url( (string) wp_get_attachment_image_url( $image, 'full' ) ); ?>" target="_blank" rel="noopener">
											<?php echo wp_get_attachment_image( $image, 'medium', false, array( 'loading' => 'lazy', 'alt' => sprintf( /* translators: %s: session */ __( 'Spread test evidence: %s', 'fxt-core' ), $label ) ) ); ?>
											<span class="u-visually-hidden"><?php esc_html_e( '(opens the full image in a new tab)', 'fxt-core' ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endforeach; ?>
</div>
