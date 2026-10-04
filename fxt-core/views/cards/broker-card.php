<?php
/**
 * Broker card (homepage / broker cards block). The whole card is clickable
 * through one real link (stretched link pattern).
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$code    = $args['code'];
$market  = Repository::market( $code );
$country = $market ? $market['name'] : $code;
?>
<article class="card card--interactive broker-card">
	<div class="broker-card__head">
		<?php echo fxt_core_logo( $broker ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div>
			<h3 class="broker-card__name"><a class="stretched-link" href="<?php echo esc_url( $broker['url'] ); ?>"><?php echo esc_html( $broker['name'] ); ?></a></h3>
			<?php echo fxt_core_badge( 'availability', Repository::availability( $broker, $code ), ' ' . $country ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
	<p class="broker-card__score">
		<span class="broker-card__score-value"><?php echo esc_html( fxt_core_score_label( $broker ) ); ?></span>
		<span class="broker-card__score-scale"><?php esc_html_e( '/ 5 research score', 'fxt-core' ); ?></span>
	</p>
	<?php echo fxt_core_regulators( $broker['regulators'], 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<dl class="kv">
		<div class="kv__row"><dt><?php esc_html_e( 'Min deposit', 'fxt-core' ); ?></dt><dd><?php echo esc_html( fxt_core_min_deposit( $broker ) ); ?></dd></div>
		<div class="kv__row"><dt><?php esc_html_e( 'Platforms', 'fxt-core' ); ?></dt><dd><?php echo esc_html( implode( ', ', fxt_core_platform_names( $broker, true ) ) ); ?></dd></div>
		<div class="kv__row"><dt><?php esc_html_e( 'Countries researched', 'fxt-core' ); ?></dt><dd>
			<?php
			/* translators: 1: researched markets, 2: total markets */
			printf( esc_html__( '%1$d of %2$d', 'fxt-core' ), count( Repository::tested_markets( $broker ) ), count( Repository::markets() ) );
			?>
		</dd></div>
	</dl>
	<div class="broker-card__foot">
		<p class="broker-card__date">
			<?php esc_html_e( 'Last reviewed', 'fxt-core' ); ?>
			<?php if ( $broker['reviewed'] ) : ?>
				<time datetime="<?php echo esc_attr( $broker['reviewed'] ); ?>"><?php echo esc_html( fxt_core_date( $broker['reviewed'] ) ); ?></time>
			<?php else : ?>
				<?php esc_html_e( 'TBD', 'fxt-core' ); ?>
			<?php endif; ?>
		</p>
		<span class="btn btn--secondary btn--sm btn--block" aria-hidden="true"><?php esc_html_e( 'Read review', 'fxt-core' ); ?></span>
	</div>
</article>
