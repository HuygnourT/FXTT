<?php
/**
 * Score weights from settings (single source). Variant "list" (homepage
 * preview) or "table" (methodology page, with stacked bar).
 *
 * @var array $args { variant: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$weights = Repository::setting( 'weights' );
$max     = max( 1, max( array_map( 'intval', $weights ) ) );
$details = array(
	'regulation'   => array( __( 'Entity, licence and permissions checked on official registers.', 'fxt-core' ), __( 'Register lookups, client agreement', 'fxt-core' ) ),
	'availability' => array( __( 'Which entity accepts residents, and whether local deposits and withdrawals work in our tests.', 'fxt-core' ), __( 'Sign-up flow, deposit and withdrawal tests', 'fxt-core' ) ),
	'costs'        => array( __( 'Spreads, commissions and swaps measured in live test sessions.', 'fxt-core' ), __( 'Cost logs from five or more sessions', 'fxt-core' ) ),
	'platforms'    => array( __( 'Platform versions, stability, order types and tools.', 'fxt-core' ), __( 'Platform test notes', 'fxt-core' ) ),
	'local'        => array( __( 'Local-language site, support hours and local payment options.', 'fxt-core' ), __( 'Support transcripts, payment tests', 'fxt-core' ) ),
	'support'      => array( __( 'Scripted questions by chat and email, scored for accuracy.', 'fxt-core' ), __( 'Chat and email transcripts', 'fxt-core' ) ),
);
$sum = array_sum( array_map( 'intval', $weights ) );

if ( 'table' === $args['variant'] ) :
	?>
	<div class="weight-stack" role="img" aria-label="<?php esc_attr_e( 'Category weights', 'fxt-core' ); ?>">
		<?php
		$i = 0;
		foreach ( Schema::categories() as $key => $label ) :
			++$i;
			?>
			<span class="weight-stack__seg weight-stack__seg--<?php echo (int) $i; ?>" style="<?php echo esc_attr( '--w: ' . (int) $weights[ $key ] . '%' ); ?>"><span><?php echo esc_html( (int) $weights[ $key ] . '%' ); ?></span></span>
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
			<?php
			$i = 0;
			foreach ( Schema::categories() as $key => $label ) :
				++$i;
				?>
				<tr>
					<th scope="row"><span class="legend-dot legend-dot--<?php echo (int) $i; ?>" aria-hidden="true"></span><?php echo esc_html( $label ); ?></th>
					<td class="u-mono"><?php echo esc_html( (int) $weights[ $key ] . '%' ); ?></td>
					<td><?php echo esc_html( $details[ $key ][0] ); ?></td>
					<td><?php echo esc_html( $details[ $key ][1] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
else :
	?>
	<ol class="weights">
		<?php foreach ( Schema::categories() as $key => $label ) : ?>
			<?php $w = (int) $weights[ $key ]; ?>
			<li class="weight">
				<div class="weight__text"><h3 class="weight__title"><?php echo esc_html( $label ); ?></h3><p class="weight__desc"><?php echo esc_html( $details[ $key ][0] ); ?></p></div>
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
