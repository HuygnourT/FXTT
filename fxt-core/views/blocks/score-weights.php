<?php
/**
 * Score categories and weights from Settings > FX Trading Today (single
 * source). Variant "list" (homepage preview) or "table" (methodology page,
 * with a stacked bar).
 *
 * @var array $args { variant: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$categories = Repository::categories();
if ( ! $categories ) {
	return;
}
$max    = max( 1, max( wp_list_pluck( $categories, 'weight' ) ) );
$sum    = array_sum( wp_list_pluck( $categories, 'weight' ) );
$swatch = static function ( $i ) {
	// Six-step colour ramp; more categories reuse it.
	return ( ( $i - 1 ) % 6 ) + 1;
};

if ( 'table' === $args['variant'] ) :
	$summary = implode(
		', ',
		array_map(
			static function ( $c ) {
				return $c['label'] . ' ' . $c['weight'] . '%';
			},
			$categories
		)
	);
	?>
	<div class="weight-stack" role="img" aria-label="<?php /* translators: %s: list of categories with weights */ echo esc_attr( sprintf( __( 'Category weights: %s', 'fxt-core' ), $summary ) ); ?>">
		<?php foreach ( $categories as $i => $category ) : ?>
			<span class="weight-stack__seg weight-stack__seg--<?php echo (int) $swatch( $i + 1 ); ?>" style="<?php echo esc_attr( '--w: ' . (int) $category['weight'] . '%' ); ?>">
				<span<?php echo $category['weight'] < 8 ? ' class="u-visually-hidden"' : ''; ?>><?php echo esc_html( (int) $category['weight'] . '%' ); ?></span>
			</span>
		<?php endforeach; ?>
	</div>
	<div class="table-scroll">
		<table class="data-table">
			<thead><tr>
				<th scope="col"><?php esc_html_e( 'Category', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Weight', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'What we measure', 'fxt-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Evidence used', 'fxt-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $categories as $i => $category ) : ?>
				<tr>
					<th scope="row"><span class="legend-dot legend-dot--<?php echo (int) $swatch( $i + 1 ); ?>" aria-hidden="true"></span><?php echo esc_html( $category['label'] ); ?></th>
					<td class="u-mono"><?php echo esc_html( (int) $category['weight'] . '%' ); ?></td>
					<td><?php echo fxt_core_text( $category['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td><?php echo fxt_core_text( $category['evidence'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
else :
	?>
	<ol class="weights">
		<?php foreach ( $categories as $category ) : ?>
			<?php $w = (int) $category['weight']; ?>
			<li class="weight">
				<div class="weight__text">
					<h3 class="weight__title"><?php echo esc_html( $category['label'] ); ?></h3>
					<?php if ( $category['description'] ) : ?>
						<p class="weight__desc"><?php echo esc_html( $category['description'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="meter" role="img" aria-label="<?php /* translators: %d: weight percent */ echo esc_attr( sprintf( __( 'Weight %d percent', 'fxt-core' ), $w ) ); ?>"><div class="meter__fill" style="<?php echo esc_attr( '--value: ' . (int) round( $w / $max * 100 ) . '%' ); ?>"></div></div>
				<span class="weight__value"><?php echo esc_html( $w . '%' ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
endif;

if ( 100 !== $sum && current_user_can( 'manage_options' ) ) {
	/* translators: %d: sum of weights */
	echo '<p class="callout callout--muted">' . esc_html( sprintf( __( 'Admin notice: weights add up to %d%%, not 100%%. Fix this in Settings > FX Trading Today.', 'fxt-core' ), $sum ) ) . '</p>';
}
