<?php
/**
 * Header and footer navigation from WordPress menus.
 *
 * Primary menu (Appearance > Menus, location "Primary"):
 *   - A top-level item with the CSS class `mega-top-brokers` opens the mega
 *     menu: highest-scoring brokers, its child items, countries and a
 *     featured link (child item with the class `mega-feature`).
 *   - Other top-level items render as plain links.
 * Footer menus 1 to 5 render as columns; the menu name is the column heading.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu items of a location as a tree.
 *
 * @param string $location Theme location.
 * @return array{menu: WP_Term|null, items: array}
 */
function fxt_tt_menu_tree( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array(
			'menu'  => null,
			'items' => array(),
		);
	}
	$menu  = wp_get_nav_menu_object( $locations[ $location ] );
	$items = $menu ? wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) ) : array();
	$items = $items ? $items : array();
	_wp_menu_item_classes_by_context( $items );

	$tree = array();
	foreach ( $items as $item ) {
		if ( ! $item->menu_item_parent ) {
			$tree[ $item->ID ] = array(
				'item'     => $item,
				'children' => array(),
			);
		}
	}
	foreach ( $items as $item ) {
		if ( $item->menu_item_parent && isset( $tree[ $item->menu_item_parent ] ) ) {
			$tree[ $item->menu_item_parent ]['children'][] = $item;
		}
	}
	return array(
		'menu'  => $menu,
		'items' => array_values( $tree ),
	);
}

/**
 * Is a menu item the current page?
 *
 * @param WP_Post $item Menu item.
 * @return bool
 */
function fxt_tt_is_current( $item ) {
	// Single records belong to their index page.
	if ( is_singular( 'fxt_evidence' ) && untrailingslashit( $item->url ) === untrailingslashit( fxt_tt_url( 'evidence' ) ) ) {
		return true;
	}
	// Links to a section or a filtered view are never "the current page".
	if ( false !== strpos( (string) $item->url, '#' ) || false !== strpos( (string) $item->url, '?' ) ) {
		return false;
	}
	if ( ! empty( $item->current ) ) {
		return true;
	}
	$path = wp_parse_url( (string) $item->url, PHP_URL_PATH );
	$here = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	return $path && '/' !== $path && trailingslashit( $path ) === trailingslashit( (string) $here );
}

/**
 * Has a class?
 *
 * @param WP_Post $item  Menu item.
 * @param string  $class Class.
 * @return bool
 */
function fxt_tt_item_has_class( $item, $class ) {
	return in_array( $class, (array) $item->classes, true );
}

/**
 * Link attributes for a menu item (target, rel, title).
 *
 * @param WP_Post $item Menu item.
 * @return string
 */
function fxt_tt_item_attrs( $item ) {
	$attrs = ' href="' . esc_url( $item->url ) . '"';
	if ( $item->target ) {
		$attrs .= ' target="' . esc_attr( $item->target ) . '"';
	}
	if ( $item->xfn ) {
		$attrs .= ' rel="' . esc_attr( $item->xfn ) . '"';
	}
	return $attrs;
}

/**
 * Desktop primary navigation.
 */
function fxt_tt_the_primary_nav() {
	$tree = fxt_tt_menu_tree( 'primary' );
	if ( ! $tree['items'] ) {
		if ( current_user_can( 'edit_theme_options' ) ) {
			echo '<nav class="primary-nav" aria-label="' . esc_attr__( 'Primary', 'fx-trading-today' ) . '"><a class="nav-link" href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Add a menu', 'fx-trading-today' ) . '</a></nav>';
		}
		return;
	}
	echo '<nav class="primary-nav" aria-label="' . esc_attr__( 'Primary', 'fx-trading-today' ) . '">';
	foreach ( $tree['items'] as $node ) {
		$item = $node['item'];
		if ( fxt_tt_item_has_class( $item, 'mega-top-brokers' ) ) {
			$active = is_singular( 'fxt_broker' ) || fxt_tt_is_current( $item );
			printf(
				'<button class="nav-link" type="button" aria-expanded="false" aria-controls="menu-%1$d" data-menu-trigger%2$s>%3$s%4$s</button>',
				(int) $item->ID,
				$active ? ' data-active' : '',
				esc_html( $item->title ),
				fxt_tt_icon( 'chevron-down' ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			continue;
		}
		printf(
			'<a class="nav-link"%s%s>%s</a>',
			fxt_tt_item_attrs( $item ), // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
			fxt_tt_is_current( $item ) ? ' aria-current="page"' : '',
			esc_html( $item->title )
		);
	}
	echo '</nav>';
}

/**
 * Mega menu panels (printed after the header bar).
 */
function fxt_tt_the_mega_menus() {
	$tree = fxt_tt_menu_tree( 'primary' );
	foreach ( $tree['items'] as $node ) {
		$item = $node['item'];
		if ( ! fxt_tt_item_has_class( $item, 'mega-top-brokers' ) ) {
			continue;
		}
		$feature = null;
		$links   = array();
		foreach ( $node['children'] as $child ) {
			if ( fxt_tt_item_has_class( $child, 'mega-feature' ) ) {
				$feature = $child;
			} else {
				$links[] = $child;
			}
		}
		$brokers = array();
		$markets = array();
		if ( fxt_tt_core() ) {
			$brokers = array_slice(
				array_values(
					array_filter(
						\FXT\Core\Repository::brokers(),
						static function ( $b ) {
							return null !== $b['score'];
						}
					)
				),
				0,
				4
			);
			$markets = \FXT\Core\Repository::markets();
		}
		?>
		<div class="mega-menu" id="menu-<?php echo (int) $item->ID; ?>" hidden data-menu-panel>
			<div class="container mega-menu__grid">
				<div>
					<p class="u-label mega-menu__heading"><?php esc_html_e( 'Highest research scores', 'fx-trading-today' ); ?></p>
					<?php foreach ( $brokers as $broker ) : ?>
						<a class="mega-menu__link" href="<?php echo esc_url( $broker['url'] ); ?>"><span><?php echo esc_html( $broker['name'] ); ?></span><span class="u-mono"><?php echo esc_html( fxt_core_score_label( $broker ) ); ?></span></a>
					<?php endforeach; ?>
					<a class="link-strong" href="<?php echo esc_url( $item->url ); ?>"><?php esc_html_e( 'All broker reviews', 'fx-trading-today' ); ?></a>
				</div>
				<?php if ( $links ) : ?>
					<div>
						<p class="u-label mega-menu__heading"><?php echo esc_html( $item->description ? $item->description : __( 'By account type', 'fx-trading-today' ) ); ?></p>
						<?php foreach ( $links as $link ) : ?>
							<a class="mega-menu__link"<?php echo fxt_tt_item_attrs( $link ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $link->title ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( $markets ) : ?>
					<div>
						<p class="u-label mega-menu__heading"><?php esc_html_e( 'By country', 'fx-trading-today' ); ?></p>
						<?php foreach ( array_slice( $markets, 0, 4 ) as $market ) : ?>
							<a class="mega-menu__link" href="<?php echo esc_url( add_query_arg( 'country', $market['code'], $item->url ) ); ?>">
								<?php
								/* translators: %s: country */
								echo esc_html( sprintf( __( 'Brokers for %s', 'fx-trading-today' ), $market['name'] ) );
								?>
							</a>
						<?php endforeach; ?>
						<a class="mega-menu__link" href="<?php echo esc_url( $item->url ); ?>">
							<?php
							/* translators: %d: number of countries */
							echo esc_html( sprintf( _n( 'All %d market', 'All %d markets', count( $markets ), 'fx-trading-today' ), count( $markets ) ) );
							?>
						</a>
					</div>
				<?php endif; ?>
				<?php if ( $feature ) : ?>
					<a class="mega-menu__feature placeholder"<?php echo fxt_tt_item_attrs( $feature ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<span class="u-label"><?php echo esc_html( $feature->description ? $feature->description : __( 'Before you read a review', 'fx-trading-today' ) ); ?></span>
						<span class="mega-menu__feature-title"><?php echo esc_html( $feature->title ); ?></span>
						<span class="link-strong"><?php echo esc_html( $feature->attr_title ? $feature->attr_title : __( 'Read the methodology', 'fx-trading-today' ) ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Mobile drawer: top-level items only.
 *
 * @param string $cta_label CTA label.
 * @param string $cta_url   CTA URL.
 */
function fxt_tt_the_mobile_nav( $cta_label, $cta_url ) {
	$tree = fxt_tt_menu_tree( 'primary' );
	?>
	<nav class="mobile-nav" id="mobile-nav" aria-label="<?php esc_attr_e( 'Mobile', 'fx-trading-today' ); ?>" hidden>
		<ul class="mobile-nav__list">
			<?php foreach ( $tree['items'] as $node ) : ?>
				<li><a<?php echo fxt_tt_item_attrs( $node['item'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo fxt_tt_is_current( $node['item'] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $node['item']->title ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $cta_label && $cta_url ) : ?>
			<a class="btn btn--primary btn--block mobile-nav__cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Footer columns from menus footer-1 to footer-5.
 */
function fxt_tt_the_footer_nav() {
	$columns = array();
	for ( $i = 1; $i <= 5; $i++ ) {
		$tree = fxt_tt_menu_tree( 'footer-' . $i );
		if ( $tree['items'] ) {
			$columns[ $i ] = $tree;
		}
	}
	if ( ! $columns ) {
		return;
	}
	echo '<div class="footer-nav">';
	foreach ( $columns as $i => $tree ) {
		$id    = 'fn-' . $i;
		$title = preg_replace( '/^FXT\s+/', '', $tree['menu']->name );
		echo '<nav aria-labelledby="' . esc_attr( $id ) . '">';
		echo '<p class="u-label footer-nav__heading" id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</p>';
		foreach ( $tree['items'] as $node ) {
			echo '<a' . fxt_tt_item_attrs( $node['item'] ) . '>' . esc_html( $node['item']->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</nav>';
	}
	echo '</div>';
}
