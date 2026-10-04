<?php
/**
 * Related broker reviews.
 *
 * @var array $args { broker: array, limit?: int }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 3;
$others = array_slice(
	array_values(
		array_filter(
			Repository::brokers(),
			static function ( $b ) use ( $broker ) {
				return $b['id'] !== $broker['id'];
			}
		)
	),
	0,
	$limit
);
if ( ! $others ) {
	return;
}
?>
<section class="related" aria-labelledby="related-title">
	<div class="container">
		<h2 class="doc-section__title" id="related-title"><?php esc_html_e( 'Other broker reviews', 'fxt-core' ); ?></h2>
		<ul class="related__grid">
			<?php foreach ( $others as $other ) : ?>
				<li class="card card--interactive related-card">
					<?php echo fxt_core_logo( $other, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<a class="stretched-link related-card__name" href="<?php echo esc_url( $other['url'] ); ?>">
						<?php
						/* translators: %s: broker name */
						printf( esc_html__( '%s review', 'fxt-core' ), esc_html( $other['name'] ) );
						?>
					</a>
					<?php echo fxt_core_score( $other ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
