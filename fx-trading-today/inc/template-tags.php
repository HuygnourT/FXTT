<?php
/**
 * Template tags. All output is escaped; functions prefixed `fxt_tt_the_` echo,
 * the others return.
 *
 * Data comes from the FX Trading Today Core plugin when it is active; every
 * tag degrades to core WordPress data when it is not.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the companion plugin active?
 *
 * @return bool
 */
function fxt_tt_core() {
	return function_exists( 'fxt_core_page_url' );
}

/**
 * URL of a key page. Used by patterns, so links never hard-code paths.
 *
 * @param string $key directory|compare|evidence|methodology|posts|home.
 * @return string
 */
function fxt_tt_url( $key ) {
	if ( 'home' === $key ) {
		return home_url( '/' );
	}
	if ( 'posts' === $key ) {
		$page = (int) get_option( 'page_for_posts' );
		return $page ? get_permalink( $page ) : home_url( '/' );
	}
	$url = fxt_tt_core() ? fxt_core_page_url( $key ) : '';
	return $url ? $url : home_url( '/' );
}

/**
 * Setting from the plugin (Settings > FX Trading Today), with a fallback.
 *
 * @param string $key      Setting key.
 * @param mixed  $fallback Fallback.
 * @return mixed
 */
function fxt_tt_setting( $key, $fallback = '' ) {
	if ( ! function_exists( 'fxt_core_setting' ) ) {
		return $fallback;
	}
	$value = fxt_core_setting( $key );
	return ( null === $value || '' === $value ) ? $fallback : $value;
}

/**
 * Prototype banner text, or '' when disabled.
 *
 * @return string
 */
function fxt_tt_banner_text() {
	return fxt_tt_setting( 'banner_enabled', false ) ? (string) fxt_tt_setting( 'banner_text' ) : '';
}

/**
 * SVG icon from the sprite.
 *
 * @param string $name  Icon name without "i-".
 * @param string $class Extra classes.
 * @return string
 */
function fxt_tt_icon( $name, $class = '' ) {
	return sprintf(
		'<svg class="icon %s" aria-hidden="true" focusable="false"><use href="#i-%s"></use></svg>',
		esc_attr( $class ),
		esc_attr( sanitize_key( $name ) )
	);
}

/**
 * Breadcrumb.
 *
 * @param array $items [ [ label, url ], ... ]; the last item is the current page.
 */
function fxt_tt_the_breadcrumb( array $items ) {
	$items = array_merge( array( array( __( 'Home', 'fx-trading-today' ), home_url( '/' ) ) ), $items );
	$last  = count( $items ) - 1;
	echo '<nav class="breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'fx-trading-today' ) . '"><ol>';
	foreach ( $items as $i => $item ) {
		if ( $i === $last || empty( $item[1] ) ) {
			echo '<li><span aria-current="page">' . esc_html( $item[0] ) . '</span></li>';
		} else {
			echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>' . fxt_tt_icon( 'chevron-right', 'icon--sm' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- icon is escaped.
		}
	}
	echo '</ol></nav>';

	// BreadcrumbList structured data from the same items.
	$list = array();
	foreach ( $items as $i => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => wp_strip_all_tags( $item[0] ),
		);
		if ( ! empty( $item[1] ) ) {
			$entry['item'] = esc_url_raw( $item[1] );
		}
		$list[] = $entry;
	}
	fxt_tt_the_json_ld(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		)
	);
}

/**
 * Print a JSON-LD block.
 *
 * @param array $data Schema.org data.
 */
function fxt_tt_the_json_ld( array $data ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
}

/**
 * Author profile (plugin user meta) with a core fallback.
 *
 * @param int $user_id User ID.
 * @return array
 */
function fxt_tt_author( $user_id ) {
	$profile = function_exists( 'fxt_core_author' ) ? fxt_core_author( $user_id ) : null;
	if ( $profile ) {
		return $profile;
	}
	$user = get_userdata( $user_id );
	$name = $user ? $user->display_name : '';
	return array(
		'id'        => $user_id,
		'name'      => $name,
		'url'       => get_author_posts_url( $user_id ),
		'initials'  => strtoupper( mb_substr( $name, 0, 1 ) ),
		'avatar_id' => 0,
		'role'      => '',
		'short_bio' => $user ? wp_trim_words( $user->description, 40 ) : '',
		'bio'       => $user ? $user->description : '',
		'simulated' => false,
	);
}

/**
 * Avatar: profile photo or initials.
 *
 * @param array  $author Author profile.
 * @param string $size   sm|lg|xl.
 * @return string
 */
function fxt_tt_avatar( array $author, $size = 'sm' ) {
	if ( ! empty( $author['avatar_id'] ) ) {
		return wp_get_attachment_image(
			(int) $author['avatar_id'],
			'thumbnail',
			false,
			array(
				'class' => 'avatar avatar--' . $size,
				'alt'   => '',
			)
		);
	}
	return '<span class="avatar avatar--' . esc_attr( $size ) . '" aria-hidden="true">' . esc_html( $author['initials'] ) . '</span>';
}

/**
 * Estimated reading time in minutes.
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function fxt_tt_reading_time( $post = null ) {
	if ( function_exists( 'fxt_core_reading_time' ) ) {
		return fxt_core_reading_time( $post );
	}
	$post = get_post( $post );
	return $post ? max( 1, (int) round( str_word_count( wp_strip_all_tags( $post->post_content ) ) / 220 ) ) : 1;
}

/**
 * Byline: avatar, linked author name, role, date and reading time.
 *
 * @param array $args { post?: WP_Post, date?: string Y-m-d, label?: string, read?: bool }.
 */
function fxt_tt_the_byline( array $args = array() ) {
	$post   = isset( $args['post'] ) ? get_post( $args['post'] ) : get_post();
	$author = fxt_tt_author( (int) $post->post_author );
	$date   = isset( $args['date'] ) ? $args['date'] : get_the_modified_date( 'Y-m-d', $post );
	$label  = isset( $args['label'] ) ? $args['label'] : __( 'Updated', 'fx-trading-today' );
	$time   = $date ? strtotime( $date . ' 12:00:00' ) : 0;
	?>
	<div class="author-line">
		<?php echo fxt_tt_avatar( $author, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<p class="author-line__text">
			<span>
				<?php
				/* translators: %s: author name with link */
				printf( esc_html__( 'By %s', 'fx-trading-today' ), '<a class="author-line__name" href="' . esc_url( $author['url'] ) . '" rel="author">' . esc_html( $author['name'] ) . '</a>' );
				?>
			</span>
			<?php if ( $author['role'] ) : ?>
				<span class="author-line__role"><?php echo esc_html( $author['role'] ); ?></span>
			<?php endif; ?>
			<?php if ( $time ) : ?>
				<span class="author-line__meta"><?php echo esc_html( $label ); ?> <time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', $time ) ); ?>"><?php echo esc_html( date_i18n( 'd M Y', $time ) ); ?></time></span>
			<?php endif; ?>
			<?php if ( ! isset( $args['read'] ) || $args['read'] ) : ?>
				<span class="author-line__meta">
					<?php
					$minutes = fxt_tt_reading_time( $post );
					/* translators: %d: minutes */
					echo esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'fx-trading-today' ), $minutes ) );
					?>
				</span>
			<?php endif; ?>
		</p>
	</div>
	<?php
}

/**
 * "About the author" box at the end of articles.
 *
 * @param int    $user_id User ID.
 * @param string $heading Label above the name.
 */
function fxt_tt_the_author_box( $user_id, $heading = '' ) {
	$author = fxt_tt_author( $user_id );
	if ( ! $author['name'] ) {
		return;
	}
	$id = wp_unique_id( 'author-box-' );
	?>
	<aside class="author-box" aria-labelledby="<?php echo esc_attr( $id ); ?>">
		<?php echo fxt_tt_avatar( $author, 'lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="author-box__body">
			<p class="u-label" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $heading ? $heading : __( 'About the author', 'fx-trading-today' ) ); ?></p>
			<p class="author-box__name"><a href="<?php echo esc_url( $author['url'] ); ?>" rel="author"><?php echo esc_html( $author['name'] ); ?></a></p>
			<?php if ( $author['role'] ) : ?>
				<p class="author-box__role"><?php echo esc_html( $author['role'] ); ?></p>
			<?php endif; ?>
			<?php if ( $author['short_bio'] ) : ?>
				<p class="author-box__bio"><?php echo esc_html( $author['short_bio'] ); ?></p>
			<?php endif; ?>
			<a class="link-strong" href="<?php echo esc_url( $author['url'] ); ?>">
				<?php
				/* translators: %s: author name */
				echo esc_html( sprintf( __( 'View %s\'s profile and reviews', 'fx-trading-today' ), $author['name'] ) );
				echo fxt_tt_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</a>
		</div>
	</aside>
	<?php
}

/**
 * Standard page hero: breadcrumb, title, excerpt as the lead.
 *
 * @param array $args { title?: string, lead?: string, crumbs?: array, class?: string, extra?: string (rendered blocks), after?: callable }.
 */
function fxt_tt_the_page_hero( array $args = array() ) {
	$title  = isset( $args['title'] ) ? $args['title'] : get_the_title();
	$lead   = isset( $args['lead'] ) ? $args['lead'] : ( has_excerpt() ? get_the_excerpt() : '' );
	$crumbs = isset( $args['crumbs'] ) ? $args['crumbs'] : fxt_tt_page_crumbs( $title );
	?>
	<header class="page-hero <?php echo esc_attr( isset( $args['class'] ) ? $args['class'] : '' ); ?>">
		<div class="container page-hero__inner">
			<?php fxt_tt_the_breadcrumb( $crumbs ); ?>
			<h1 class="page-hero__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $lead ) : ?>
				<p class="page-hero__lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>
			<?php
			if ( ! empty( $args['extra'] ) ) {
				echo $args['extra']; // phpcs:ignore WordPress.Security.EscapeOutput -- rendered block markup.
			}
			if ( ! empty( $args['after'] ) && is_callable( $args['after'] ) ) {
				call_user_func( $args['after'] );
			}
			?>
		</div>
	</header>
	<?php
}

/**
 * Breadcrumb items for a page, including its ancestors.
 *
 * @param string $title Current title.
 * @return array
 */
function fxt_tt_page_crumbs( $title ) {
	$crumbs = array();
	if ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor ) {
			$crumbs[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
	}
	$crumbs[] = array( $title, '' );
	return $crumbs;
}

/**
 * Pagination for archives.
 */
function fxt_tt_the_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => __( 'Previous', 'fx-trading-today' ),
			'next_text' => __( 'Next', 'fx-trading-today' ),
			'class'     => 'pagination',
		)
	);
}

/**
 * Article card used by the blog, archives and search.
 */
function fxt_tt_the_article_item() {
	$cats = get_the_category();
	?>
	<article <?php post_class( 'article-item' ); ?>>
		<?php if ( $cats ) : ?>
			<p class="u-label u-label--accent"><?php echo esc_html( $cats[0]->name ); ?></p>
		<?php elseif ( 'post' !== get_post_type() ) : ?>
			<p class="u-label u-label--accent"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></p>
		<?php endif; ?>
		<h2 class="article-item__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php if ( has_excerpt() || 'post' === get_post_type() ) : ?>
			<p class="article-item__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<p class="byline">
			<span><?php the_author_posts_link(); ?></span>
			<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
		</p>
	</article>
	<?php
}

/**
 * Split the current post's blocks: leading blocks with the class
 * `page-hero__extra` render inside the page header (e.g. research-area chips
 * or principles), the rest is the body. Editors control this from the block's
 * "Additional CSS class" field; nothing is hard-coded per page.
 *
 * @return array{hero: string, body: string}
 */
function fxt_tt_content_parts() {
	static $cache = array();
	$id = get_the_ID();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$content = get_the_content();
	$hero    = '';
	if ( has_blocks( $content ) ) {
		$blocks = parse_blocks( $content );
		while ( $blocks ) {
			$first = $blocks[0];
			if ( null === $first['blockName'] && '' === trim( $first['innerHTML'] ) ) {
				array_shift( $blocks );
				continue;
			}
			$class = isset( $first['attrs']['className'] ) ? ' ' . $first['attrs']['className'] . ' ' : '';
			if ( false === strpos( $class, ' page-hero__extra ' ) ) {
				break;
			}
			$hero .= render_block( array_shift( $blocks ) );
		}
		$content = serialize_blocks( $blocks );
	}
	/** This filter is documented in wp-includes/post-template.php */
	$body           = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', $content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
	$cache[ $id ] = array(
		'hero' => $hero,
		'body' => $body,
	);
	return $cache[ $id ];
}
