<?php
/**
 * Directory row (broker reviews listing).
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
$author  = Repository::author( $broker['author_id'] );
$compare = fxt_core_compare_url( array( $broker['slug'] ) );
?>
<article class="card card--interactive review-row">
	<div class="review-row__main">
		<div class="review-row__id">
			<?php echo fxt_core_logo( $broker ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="review-row__name-wrap">
				<h3 class="review-row__name"><a class="stretched-link" href="<?php echo esc_url( $broker['url'] ); ?>"><?php echo esc_html( $broker['name'] ); ?></a></h3>
				<div class="review-row__badges">
					<?php echo fxt_core_badge( 'status', $broker['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo fxt_core_badge( 'availability', Repository::availability( $broker, $code ), ' ' . $country ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
		<?php if ( $broker['excerpt'] ) : ?>
			<p class="review-row__verdict"><?php echo esc_html( $broker['excerpt'] ); ?></p>
		<?php endif; ?>
		<?php if ( $author ) : ?>
			<p class="review-row__author">
				<?php
				printf(
					/* translators: 1: author link, 2: author role */
					esc_html__( 'Reviewed by %1$s, %2$s', 'fxt-core' ),
					'<a href="' . esc_url( $author['url'] ) . '" rel="author">' . esc_html( $author['name'] ) . '</a>',
					esc_html( $author['role'] )
				);
				?>
			</p>
		<?php endif; ?>
		<dl class="review-row__facts">
			<div class="review-row__fact review-row__fact--wide">
				<dt><?php esc_html_e( 'Regulation summary', 'fxt-core' ); ?> <span class="unverified"><?php esc_html_e( 'unverified', 'fxt-core' ); ?></span></dt>
				<dd><?php echo fxt_core_regulators( $broker['regulators'], 4 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
			</div>
			<div class="review-row__fact"><dt><?php esc_html_e( 'Min deposit', 'fxt-core' ); ?></dt><dd><?php echo esc_html( fxt_core_min_deposit( $broker ) ); ?></dd></div>
			<div class="review-row__fact"><dt><?php esc_html_e( 'Platforms', 'fxt-core' ); ?></dt><dd><?php echo fxt_core_text( implode( ', ', fxt_core_platform_names( $broker ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			<div class="review-row__fact"><dt><?php esc_html_e( 'Countries researched', 'fxt-core' ); ?></dt><dd>
				<?php
				/* translators: 1: researched markets, 2: total markets */
				printf( esc_html__( '%1$d of %2$d', 'fxt-core' ), count( Repository::tested_markets( $broker ) ), count( Repository::researched_markets() ) );
				?>
			</dd></div>
			<div class="review-row__fact"><dt><?php esc_html_e( 'Last reviewed', 'fxt-core' ); ?></dt><dd><?php echo esc_html( fxt_core_date( $broker['reviewed'] ) ); ?></dd></div>
		</dl>
	</div>
	<div class="review-row__side">
		<p class="review-row__score"><?php echo fxt_core_score( $broker ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="review-row__score-label"><?php esc_html_e( 'Research score', 'fxt-core' ); ?></span></p>
		<a class="btn btn--primary btn--sm review-row__cta" href="<?php echo esc_url( $broker['url'] ); ?>"><?php esc_html_e( 'Read Full Review', 'fxt-core' ); ?><span class="u-visually-hidden">: <?php echo esc_html( $broker['name'] ); ?></span></a>
		<?php if ( $compare ) : ?>
			<a class="link-strong review-row__compare" href="<?php echo esc_url( $compare ); ?>"><?php esc_html_e( 'Add to comparison', 'fxt-core' ); ?><span class="u-visually-hidden">: <?php echo esc_html( $broker['name'] ); ?></span></a>
		<?php endif; ?>
	</div>
</article>
