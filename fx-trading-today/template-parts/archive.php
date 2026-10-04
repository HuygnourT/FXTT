<?php
/**
 * Shared list layout for the blog, archives and search.
 *
 * @var array $args { title: string, lead: string, search?: bool }
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

fxt_tt_the_page_hero(
	array(
		'title'  => $args['title'],
		'lead'   => $args['lead'],
		'crumbs' => array( array( $args['title'], '' ) ),
	)
);
?>
<div class="container page-body page-body--list">
	<?php if ( ! empty( $args['search'] ) ) : ?>
		<div class="page-body__search"><?php get_search_form(); ?></div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="article-list">
			<?php
			while ( have_posts() ) :
				the_post();
				fxt_tt_the_article_item();
			endwhile;
			?>
		</div>
		<?php fxt_tt_the_pagination(); ?>
	<?php else : ?>
		<p class="empty-state"><?php esc_html_e( 'Nothing found. Try another search or browse the broker reviews.', 'fx-trading-today' ); ?></p>
		<p><a class="btn btn--primary" href="<?php echo esc_url( fxt_tt_url( 'directory' ) ); ?>"><?php esc_html_e( 'Browse broker reviews', 'fx-trading-today' ); ?></a></p>
	<?php endif; ?>
</div>
