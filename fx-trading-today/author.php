<?php
/**
 * Author profile: bio, research record, reviews, evidence and articles.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

$fxt_user   = get_queried_object();
$fxt_author = fxt_tt_author( (int) $fxt_user->ID );
$fxt_name   = $fxt_author['name'];

$fxt_reviews  = array();
$fxt_evidence = array();
if ( fxt_tt_core() ) {
	foreach ( \FXT\Core\Repository::brokers() as $fxt_b ) {
		if ( (int) $fxt_b['author_id'] === (int) $fxt_user->ID ) {
			$fxt_reviews[] = $fxt_b;
		}
	}
	usort(
		$fxt_reviews,
		static function ( $a, $b ) {
			return strcmp( (string) $b['reviewed'], (string) $a['reviewed'] );
		}
	);
	$fxt_evidence = get_posts(
		array(
			'post_type'      => 'fxt_evidence',
			'author'         => $fxt_user->ID,
			'posts_per_page' => 20,
		)
	);
}
$fxt_articles = get_posts(
	array(
		'post_type'      => array( 'post', 'page' ),
		'author'         => $fxt_user->ID,
		'posts_per_page' => 10,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small author archive.
			'relation' => 'OR',
			array(
				'key'     => '_wp_page_template',
				'value'   => 'templates/page-document.php',
			),
			array(
				'key'     => '_wp_page_template',
				'compare' => 'NOT EXISTS',
			),
		),
	)
);
$fxt_articles = array_values(
	array_filter(
		$fxt_articles,
		static function ( $p ) {
			return 'post' === $p->post_type || 'templates/page-document.php' === get_page_template_slug( $p );
		}
	)
);

get_header();
?>
<header class="page-hero author-hero">
	<div class="container page-hero__inner">
		<?php fxt_tt_the_breadcrumb( array( array( $fxt_name, '' ) ) ); ?>
		<div class="author-hero__grid">
			<?php echo fxt_tt_avatar( $fxt_author, 'xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="author-hero__copy">
				<?php if ( ! empty( $fxt_author['simulated'] ) && function_exists( 'fxt_core_sample' ) ) : ?>
					<div class="review-hero__badges"><?php echo fxt_core_sample( __( 'Simulated profile', 'fx-trading-today' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php endif; ?>
				<h1 class="page-hero__title"><?php echo esc_html( $fxt_name ); ?></h1>
				<?php if ( $fxt_author['role'] ) : ?>
					<p class="author-hero__role"><?php echo esc_html( $fxt_author['role'] ); ?></p>
				<?php endif; ?>
				<?php if ( $fxt_author['short_bio'] ) : ?>
					<p class="page-hero__lead"><?php echo esc_html( $fxt_author['short_bio'] ); ?></p>
				<?php endif; ?>
				<?php
				$fxt_meta = array_filter(
					array(
						__( 'Based in', 'fx-trading-today' )        => isset( $fxt_author['location'] ) ? $fxt_author['location'] : '',
						__( 'Reviewing since', 'fx-trading-today' ) => isset( $fxt_author['since'] ) ? $fxt_author['since'] : '',
						__( 'Languages', 'fx-trading-today' )       => ! empty( $fxt_author['languages'] ) ? implode( ', ', $fxt_author['languages'] ) : '',
						__( 'Markets tested in person', 'fx-trading-today' ) => ! empty( $fxt_author['markets'] ) ? implode(
							', ',
							array_map(
								static function ( $code ) {
									$m = function_exists( 'fxt_core_market' ) ? fxt_core_market( $code ) : null;
									return $m ? $m['name'] : $code;
								},
								$fxt_author['markets']
							)
						) : '',
					)
				);
				?>
				<?php if ( $fxt_meta ) : ?>
					<dl class="meta-strip">
						<?php foreach ( $fxt_meta as $fxt_label => $fxt_value ) : ?>
							<div><dt><?php echo esc_html( $fxt_label ); ?></dt><dd><?php echo esc_html( $fxt_value ); ?></dd></div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>

<?php if ( ! empty( $fxt_author['stats'] ) ) : ?>
	<section class="author-stats" aria-label="<?php esc_attr_e( 'Research record', 'fx-trading-today' ); ?>">
		<div class="container author-stats__inner">
			<?php foreach ( $fxt_author['stats'] as $fxt_stat ) : ?>
				<p class="trust-stat"><span class="trust-stat__value"><?php echo esc_html( $fxt_stat['value'] ); ?></span><span class="trust-stat__label"><?php echo esc_html( $fxt_stat['label'] ); ?></span></p>
			<?php endforeach; ?>
			<?php echo function_exists( 'fxt_core_sample' ) ? fxt_core_sample( __( 'Sample figures', 'fx-trading-today' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
<?php endif; ?>

<div class="container author-body">
	<?php if ( $fxt_author['bio'] ) : ?>
		<section class="doc-section" id="about" aria-labelledby="about-title">
			<h2 class="doc-section__title" id="about-title">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'About %s', 'fx-trading-today' ), $fxt_name ) );
				?>
			</h2>
			<div class="author-about">
				<div class="author-about__bio"><?php echo wp_kses_post( wpautop( $fxt_author['bio'] ) ); ?></div>
				<?php if ( ! empty( $fxt_author['expertise'] ) ) : ?>
					<div class="author-about__side">
						<p class="u-label"><?php esc_html_e( 'Areas of expertise', 'fx-trading-today' ); ?></p>
						<ul class="chip-list">
							<?php foreach ( $fxt_author['expertise'] as $fxt_item ) : ?>
								<li class="chip"><?php echo esc_html( $fxt_item ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $fxt_author['principles'] ) ) : ?>
		<section class="doc-section" id="principles" aria-labelledby="principles-title">
			<h2 class="doc-section__title" id="principles-title">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'How %s reviews brokers', 'fx-trading-today' ), $fxt_name ) );
				?>
			</h2>
			<ul class="check-list">
				<?php foreach ( $fxt_author['principles'] as $fxt_item ) : ?>
					<li><?php echo fxt_tt_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $fxt_item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $fxt_author['disclosure'] ) ) : ?>
				<p class="callout"><?php echo fxt_tt_icon( 'shield-check', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php echo esc_html( $fxt_author['disclosure'] ); ?> <a href="<?php echo esc_url( fxt_tt_url( 'methodology' ) . '#independence' ); ?>"><?php esc_html_e( 'Editorial independence', 'fx-trading-today' ); ?></a></span></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( $fxt_reviews ) : ?>
		<section class="doc-section" id="reviews" aria-labelledby="author-reviews-title">
			<h2 class="doc-section__title" id="author-reviews-title">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'Broker reviews by %s', 'fx-trading-today' ), $fxt_name ) );
				?>
				<span class="count"><?php echo esc_html( (string) count( $fxt_reviews ) ); ?></span>
			</h2>
			<ul class="author-list">
				<?php foreach ( $fxt_reviews as $fxt_b ) : ?>
					<li class="author-list__item">
						<?php echo fxt_core_logo( $fxt_b, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div class="author-list__main">
							<a class="author-list__title" href="<?php echo esc_url( $fxt_b['url'] ); ?>">
								<?php
								/* translators: %s: broker name */
								echo esc_html( sprintf( __( '%s review', 'fx-trading-today' ), $fxt_b['name'] ) );
								?>
							</a>
							<span class="author-list__meta">
								<?php
								/* translators: %s: date */
								echo esc_html( $fxt_b['reviewed'] ? sprintf( __( 'Last reviewed %s', 'fx-trading-today' ), fxt_core_date( $fxt_b['reviewed'] ) ) : __( 'Research pending', 'fx-trading-today' ) );
								?>
							</span>
						</div>
						<?php echo fxt_core_badge( 'status', $fxt_b['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo fxt_core_score( $fxt_b ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $fxt_evidence ) : ?>
		<section class="doc-section" id="evidence" aria-labelledby="author-evidence-title">
			<h2 class="doc-section__title" id="author-evidence-title">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'Test evidence by %s', 'fx-trading-today' ), $fxt_name ) );
				?>
				<span class="count"><?php echo esc_html( (string) count( $fxt_evidence ) ); ?></span>
			</h2>
			<ul class="link-cards">
				<?php
				foreach ( $fxt_evidence as $fxt_post ) :
					$fxt_ev = \FXT\Core\Repository::evidence( $fxt_post->ID );
					$fxt_n  = $fxt_ev ? count( $fxt_ev['deposits'] ) + count( $fxt_ev['withdrawals'] ) : 0;
					?>
					<li>
						<a class="link-card" href="<?php echo esc_url( get_permalink( $fxt_post ) ); ?>">
							<span class="u-label">
								<?php
								/* translators: %d: number of tests */
								echo esc_html( sprintf( _n( '%d payment test', '%d payment tests', $fxt_n, 'fx-trading-today' ), $fxt_n ) );
								?>
							</span>
							<span class="link-card__text"><?php echo esc_html( get_the_title( $fxt_post ) ); ?></span>
							<?php echo fxt_tt_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $fxt_articles ) : ?>
		<section class="doc-section" id="guides" aria-labelledby="author-guides-title">
			<h2 class="doc-section__title" id="author-guides-title">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'Guides and methodology by %s', 'fx-trading-today' ), $fxt_name ) );
				?>
			</h2>
			<ul class="author-list">
				<?php
				foreach ( $fxt_articles as $fxt_post ) :
					$fxt_cats  = get_the_category( $fxt_post->ID );
					$fxt_label = $fxt_cats ? $fxt_cats[0]->name : __( 'Methodology', 'fx-trading-today' );
					?>
					<li class="author-list__item">
						<div class="author-list__main">
							<span class="u-label u-label--accent"><?php echo esc_html( $fxt_label ); ?></span>
							<a class="author-list__title" href="<?php echo esc_url( get_permalink( $fxt_post ) ); ?>"><?php echo esc_html( get_the_title( $fxt_post ) ); ?></a>
							<span class="author-list__meta">
								<?php
								/* translators: %s: date */
								echo esc_html( sprintf( __( 'Updated %s', 'fx-trading-today' ), get_the_modified_date( 'd M Y', $fxt_post ) ) );
								?>
							</span>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
<?php
fxt_tt_the_json_ld(
	array(
		'@context'   => 'https://schema.org',
		'@type'      => 'ProfilePage',
		'mainEntity' => array_filter(
			array(
				'@type'         => 'Person',
				'name'          => $fxt_name,
				'jobTitle'      => $fxt_author['role'],
				'description'   => $fxt_author['short_bio'],
				'knowsAbout'    => isset( $fxt_author['expertise'] ) ? $fxt_author['expertise'] : null,
				'knowsLanguage' => isset( $fxt_author['languages'] ) ? $fxt_author['languages'] : null,
				'worksFor'      => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
				),
			)
		),
	)
);
get_footer();
