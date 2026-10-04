<?php
/**
 * Single article.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$fxt_posts_page = (int) get_option( 'page_for_posts' );
	$fxt_crumbs     = array();
	if ( $fxt_posts_page ) {
		$fxt_crumbs[] = array( get_the_title( $fxt_posts_page ), get_permalink( $fxt_posts_page ) );
	}
	$fxt_crumbs[] = array( get_the_title(), '' );
	$fxt_cats     = get_the_category();
	?>
	<article <?php post_class(); ?>>
		<header class="page-hero">
			<div class="container page-hero__inner">
				<?php fxt_tt_the_breadcrumb( $fxt_crumbs ); ?>
				<?php if ( $fxt_cats ) : ?>
					<p class="u-label u-label--accent"><a href="<?php echo esc_url( get_category_link( $fxt_cats[0] ) ); ?>"><?php echo esc_html( $fxt_cats[0]->name ); ?></a></p>
				<?php endif; ?>
				<h1 class="page-hero__title"><?php the_title(); ?></h1>
				<?php fxt_tt_the_byline( array( 'date' => get_the_modified_date( 'Y-m-d' ) ) ); ?>
				<?php if ( has_excerpt() ) : ?>
					<p class="page-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="container entry-media"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>
		<div class="container doc-layout">
			<nav class="toc" aria-label="<?php esc_attr_e( 'On this page', 'fx-trading-today' ); ?>" data-toc hidden>
				<p class="u-label toc__title"><?php esc_html_e( 'On this page', 'fx-trading-today' ); ?></p>
			</nav>
			<div class="doc-content entry-content prose">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
				<?php fxt_tt_the_author_box( (int) get_the_author_meta( 'ID' ) ); ?>
				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
		</div>
	</article>
	<?php
endwhile;
get_footer();
