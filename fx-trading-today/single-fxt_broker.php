<?php
/**
 * Broker review.
 *
 * Hero, country context and score card come from structured data (plugin
 * views); the body is the review's block content (editorial blocks and
 * fxt/broker-data sections) with a table of contents built from its H2s.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

if ( ! fxt_tt_core() ) {
	require get_query_template( 'single' );
	return;
}

get_header();
while ( have_posts() ) :
	the_post();
	$fxt_broker  = fxt_core_broker();
	$fxt_code    = fxt_core_current_market();
	$fxt_market  = fxt_core_market( $fxt_code );
	$fxt_country = $fxt_market ? $fxt_market['name'] : $fxt_code;
	$fxt_avail   = \FXT\Core\Repository::availability( $fxt_broker, $fxt_code );
	?>
	<article <?php post_class(); ?>>
		<header class="review-hero">
			<div class="container">
				<?php
				fxt_tt_the_breadcrumb(
					array(
						array( __( 'Broker Reviews', 'fx-trading-today' ), fxt_tt_url( 'directory' ) ),
						array( $fxt_broker['name'], '' ),
					)
				);
				?>
				<div class="review-hero__grid">
					<div class="review-hero__copy">
						<div class="review-hero__badges">
							<?php
							echo fxt_core_badge( 'status', $fxt_broker['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput
							/* translators: %s: country */
							echo fxt_core_badge( 'availability', $fxt_avail, sprintf( __( ' in %s', 'fx-trading-today' ), $fxt_country ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo fxt_core_sample(); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
						<h1 class="page-hero__title">
							<?php
							/* translators: %s: broker name */
							echo esc_html( sprintf( __( '%s review', 'fx-trading-today' ), $fxt_broker['name'] ) );
							?>
						</h1>
						<?php
						fxt_tt_the_byline(
							array(
								'date'  => $fxt_broker['reviewed'],
								'label' => __( 'Last reviewed', 'fx-trading-today' ),
								'read'  => (bool) $fxt_broker['reviewed'],
							)
						);
						?>
						<?php if ( has_excerpt() ) : ?>
							<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
						<?php fxt_core_view( 'broker/country-context', array( 'broker' => $fxt_broker, 'code' => $fxt_code ) ); ?>
						<?php fxt_core_view( 'broker/actions', array( 'broker' => $fxt_broker ) ); ?>
						<?php if ( fxt_tt_setting( 'sample_labels', false ) ) : ?>
							<p class="review-hero__meta"><?php esc_html_e( 'Research and test data on this page are sample data.', 'fx-trading-today' ); ?></p>
						<?php endif; ?>
					</div>
					<?php fxt_core_view( 'broker/score-card', array( 'broker' => $fxt_broker ) ); ?>
				</div>
			</div>
		</header>

		<div class="container doc-layout">
			<nav class="toc" aria-label="<?php esc_attr_e( 'On this page', 'fx-trading-today' ); ?>" data-toc hidden>
				<p class="u-label toc__title"><?php esc_html_e( 'On this page', 'fx-trading-today' ); ?></p>
			</nav>
			<div class="doc-content entry-content prose prose--review">
				<?php the_content(); ?>
				<?php fxt_tt_the_author_box( (int) get_the_author_meta( 'ID' ), __( 'Reviewed by', 'fx-trading-today' ) ); ?>
			</div>
		</div>

		<?php fxt_core_view( 'broker/related', array( 'broker' => $fxt_broker ) ); ?>
	</article>
	<?php
	$fxt_review = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Review',
		'name'          => get_the_title(),
		'author'        => array(
			'@type' => 'Person',
			'name'  => get_the_author(),
			'url'   => get_author_posts_url( (int) get_the_author_meta( 'ID' ) ),
		),
		'itemReviewed'  => array(
			'@type' => 'FinancialService',
			'name'  => $fxt_broker['name'],
		),
		'datePublished' => get_the_date( 'c' ),
		'dateModified'  => get_the_modified_date( 'c' ),
	);
	// A rating is only exposed once research is complete and the data is not sample data.
	if ( null !== $fxt_broker['score'] && ! fxt_tt_setting( 'sample_labels', false ) ) {
		$fxt_review['reviewRating'] = array(
			'@type'       => 'Rating',
			'ratingValue' => $fxt_broker['score'],
			'bestRating'  => 5,
			'worstRating' => 0,
		);
	}
	fxt_tt_the_json_ld( $fxt_review );
endwhile;
get_footer();
