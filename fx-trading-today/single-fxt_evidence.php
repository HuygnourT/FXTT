<?php
/**
 * Test evidence record.
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
	$fxt_evidence = \FXT\Core\Repository::evidence( get_the_ID() );
	$fxt_broker   = $fxt_evidence ? fxt_core_broker( $fxt_evidence['broker_id'] ) : null;
	$fxt_market   = $fxt_evidence ? fxt_core_market( $fxt_evidence['market'] ) : null;
	$fxt_country  = $fxt_market ? $fxt_market['name'] : '';
	$fxt_done     = $fxt_evidence && 'completed' === $fxt_evidence['status'];
	$fxt_crumbs   = array( array( __( 'Broker Reviews', 'fx-trading-today' ), fxt_tt_url( 'directory' ) ) );
	if ( $fxt_broker ) {
		$fxt_crumbs[] = array( $fxt_broker['name'], $fxt_broker['url'] );
		if ( $fxt_country ) {
			$fxt_crumbs[] = array( $fxt_country, add_query_arg( 'country', $fxt_evidence['market'], $fxt_broker['url'] ) . '#payments' );
		}
	}
	$fxt_crumbs[] = array( __( 'Test Evidence', 'fx-trading-today' ), '' );
	?>
	<article <?php post_class(); ?>>
		<header class="page-hero evidence-hero">
			<div class="container page-hero__inner">
				<?php fxt_tt_the_breadcrumb( $fxt_crumbs ); ?>
				<div class="review-hero__badges">
					<span class="badge <?php echo esc_attr( $fxt_done ? 'badge--published' : 'badge--progress' ); ?>"><?php echo esc_html( $fxt_done ? __( 'Completed', 'fx-trading-today' ) : __( 'In progress', 'fx-trading-today' ) ); ?></span>
					<?php echo fxt_core_sample( __( 'Sample data for prototype purposes', 'fx-trading-today' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<h1 class="page-hero__title"><?php the_title(); ?></h1>
				<?php
				fxt_tt_the_byline(
					array(
						'date'  => $fxt_evidence ? $fxt_evidence['period']['to'] : get_the_date( 'Y-m-d' ),
						'label' => __( 'Published', 'fx-trading-today' ),
					)
				);
				?>
				<?php if ( has_excerpt() ) : ?>
					<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<?php
				if ( $fxt_evidence ) {
					fxt_core_view( 'evidence/meta', array( 'evidence' => $fxt_evidence ) );
				}
				?>
			</div>
		</header>

		<div class="container evidence-body entry-content prose prose--evidence">
			<?php the_content(); ?>
			<?php if ( $fxt_broker ) : ?>
				<ul class="link-cards">
					<li>
						<a class="link-card" href="<?php echo esc_url( $fxt_broker['url'] ); ?>">
							<span class="u-label"><?php esc_html_e( 'Full review', 'fx-trading-today' ); ?></span>
							<span class="link-card__text">
								<?php
								/* translators: %s: broker name */
								echo esc_html( sprintf( __( '%s review', 'fx-trading-today' ), $fxt_broker['name'] ) );
								?>
							</span>
							<?php echo fxt_tt_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</li>
					<li>
						<a class="link-card" href="<?php echo esc_url( fxt_tt_url( 'methodology' ) . '#testing' ); ?>">
							<span class="u-label"><?php esc_html_e( 'Methodology', 'fx-trading-today' ); ?></span>
							<span class="link-card__text"><?php esc_html_e( 'How we run payment tests', 'fx-trading-today' ); ?></span>
							<?php echo fxt_tt_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</li>
				</ul>
			<?php endif; ?>
			<?php fxt_tt_the_author_box( (int) get_the_author_meta( 'ID' ), __( 'Tested and written by', 'fx-trading-today' ) ); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
