<?php
/**
 * Broker hero: research score with weighted category breakdown.
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$method  = fxt_core_page_url( 'methodology' );
$has     = null !== $broker['score'] && ! empty( $broker['breakdown'] );
?>
<aside class="card score-card" aria-label="<?php esc_attr_e( 'Research score breakdown', 'fxt-core' ); ?>">
	<div class="score-card__head">
		<span class="u-label"><?php esc_html_e( 'Research score', 'fxt-core' ); ?></span>
		<p class="score-card__value">
			<?php if ( null === $broker['score'] ) : ?>
				<?php esc_html_e( 'Pending', 'fxt-core' ); ?>
			<?php else : ?>
				<?php echo esc_html( number_format_i18n( $broker['score'], 1 ) ); ?><span>/ 5</span>
			<?php endif; ?>
		</p>
	</div>
	<?php if ( $has ) : ?>
		<ul class="score-card__list">
			<?php
			foreach ( Repository::categories() as $category ) :
				$key   = $category['key'];
				$label = $category['label'];
				$value = isset( $broker['breakdown'][ $key ] ) ? (float) $broker['breakdown'][ $key ] : 0;
				$pct   = (int) round( $value / 5 * 100 );
				?>
				<li class="score-card__row">
					<span class="score-card__label"><?php echo esc_html( $label ); ?> <span class="u-mono muted"><?php echo esc_html( (int) $category['weight'] . '%' ); ?></span></span>
					<span class="meter meter--thin" role="img" aria-label="<?php /* translators: 1: category, 2: score */ echo esc_attr( sprintf( __( '%1$s: %2$s out of 5', 'fxt-core' ), $label, number_format_i18n( $value, 1 ) ) ); ?>"><span class="meter__fill" style="<?php echo esc_attr( '--value: ' . $pct . '%' ); ?>"></span></span>
					<span class="score-card__num"><?php echo esc_html( number_format_i18n( $value, 1 ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="score-card__pending"><?php esc_html_e( 'No score is published until entity mapping and payment tests are complete.', 'fxt-core' ); ?></p>
	<?php endif; ?>
	<?php if ( $method ) : ?>
		<a class="link-strong" href="<?php echo esc_url( $method . '#framework' ); ?>"><?php esc_html_e( 'How the score is calculated', 'fxt-core' ); ?></a>
	<?php endif; ?>
</aside>
