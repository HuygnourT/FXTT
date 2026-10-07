<?php
/**
 * Header and footer navigation from WordPress menus.
 *
 * Primary menu (Appearance > Menus, location "Primary"):
 *   - A top-level item with the CSS class `mega` opens a mega menu built
 *     entirely from its child items (no text lives in this file):
 *       - child with class `mega-auto-brokers`: column of the highest-scoring
 *         brokers. Navigation Label = heading, URL + Description = the link
 *         under the list.
 *       - child with class `mega-auto-countries`: column of researched
 *         countries linking to the directory. Same fields; "%d" in the
 *         Description is replaced with the number of countries.
 *       - child with class `mega-feature`: featured card. Description = small
 *         label, Navigation Label = title, Title Attribute = call to action.
 *       - any other child: a link column. Its Navigation Label is the heading
 *         and its own child items are the links.
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

	// Build a nested tree (any depth) keyed by parent ID.
	$by_parent = array();
	foreach ( $items as $item ) {
		$by_parent[ (int) $item->menu_item_parent ][] = $item;
	}
	$build = static function ( $parent ) use ( &$build, $by_parent ) {
		$nodes = array();
		foreach ( isset( $by_parent[ $parent ] ) ? $by_parent[ $parent ] : array() as $item ) {
			$nodes[] = array(
				'item'     => $item,
				'children' => $build( (int) $item->ID ),
			);
		}
		return $nodes;
	};
	return array(
		'menu'  => $menu,
		'items' => $build( 0 ),
	);
}

/**
 * Does a top-level item open a mega menu?
 *
 * @param WP_Post $item Menu item.
 * @return bool
 */
function fxt_tt_is_mega( $item ) {
	// "mega-top-brokers" is the class used by version 1.0 menus.
	return fxt_tt_item_has_class( $item, 'mega' ) || fxt_tt_item_has_class( $item, 'mega-top-brokers' );
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
		if ( fxt_tt_is_mega( $item ) && $node['children'] ) {
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
		if ( ! fxt_tt_is_mega( $node['item'] ) || ! $node['children'] ) {
			continue;
		}
		?>
		<div class="mega-menu" id="menu-<?php echo (int) $node['item']->ID; ?>" hidden data-menu-panel>
			<div class="container mega-menu__grid">
				<?php
				$loose = array();
				foreach ( $node['children'] as $child ) {
					$item = $child['item'];
					if ( fxt_tt_item_has_class( $item, 'mega-feature' ) ) {
						fxt_tt_the_mega_feature( $item );
					} elseif ( fxt_tt_item_has_class( $item, 'mega-auto-brokers' ) ) {
						fxt_tt_the_mega_auto_column( $item, 'brokers' );
					} elseif ( fxt_tt_item_has_class( $item, 'mega-auto-countries' ) ) {
						fxt_tt_the_mega_auto_column( $item, 'countries' );
					} elseif ( $child['children'] ) {
						fxt_tt_the_mega_column( $item->title, wp_list_pluck( $child['children'], 'item' ) );
					} else {
						$loose[] = $item;
					}
				}
				if ( $loose ) {
					// Version 1.0 menus: plain child links form one column headed by the parent's Description.
					fxt_tt_the_mega_column( (string) $node['item']->description, $loose );
				}
				?>
			</div>
		</div>
		<?php
	}
}

/**
 * Link column.
 *
 * @param string    $heading Column heading.
 * @param WP_Post[] $links   Menu items.
 */
function fxt_tt_the_mega_column( $heading, array $links ) {
	echo '<div>';
	if ( '' !== trim( $heading ) ) {
		echo '<p class="u-label mega-menu__heading">' . esc_html( $heading ) . '</p>';
	}
	foreach ( $links as $link ) {
		echo '<a class="mega-menu__link"' . fxt_tt_item_attrs( $link ) . '>' . esc_html( $link->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
	}
	echo '</div>';
}

/**
 * Automatic column: top brokers or researched countries (data from the plugin).
 *
 * @param WP_Post $item Menu item (title = heading, URL + description = footer link).
 * @param string  $type brokers|countries.
 */
function fxt_tt_the_mega_auto_column( $item, $type ) {
	if ( ! fxt_tt_core() ) {
		return;
	}
	$footer = trim( (string) $item->description );
	echo '<div>';
	echo '<p class="u-label mega-menu__heading">' . esc_html( $item->title ) . '</p>';
	if ( 'brokers' === $type ) {
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
		foreach ( $brokers as $broker ) {
			echo '<a class="mega-menu__link" href="' . esc_url( $broker['url'] ) . '"><span>' . esc_html( $broker['name'] ) . '</span><span class="u-mono">' . esc_html( fxt_core_score_label( $broker ) ) . '</span></a>';
		}
		$count = count( \FXT\Core\Repository::brokers() );
	} else {
		$markets = \FXT\Core\Repository::researched_markets();
		// Most complete research first (published, in progress, planned), then A to Z.
		$rank = array_flip( array( 'published', 'in-progress', 'planned' ) );
		usort(
			$markets,
			static function ( $a, $b ) use ( $rank ) {
				$ra = isset( $rank[ $a['status'] ] ) ? $rank[ $a['status'] ] : 9;
				$rb = isset( $rank[ $b['status'] ] ) ? $rank[ $b['status'] ] : 9;
				return $ra === $rb ? strcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) ) : $ra - $rb;
			}
		);
		$target  = $item->url && '#' !== $item->url ? $item->url : fxt_tt_url( 'directory' );
		foreach ( array_slice( $markets, 0, 4 ) as $market ) {
			echo '<a class="mega-menu__link" href="' . esc_url( add_query_arg( 'country', $market['code'], $target ) ) . '">' . esc_html( $market['name'] ) . '</a>';
		}
		$count = count( $markets );
	}
	if ( $footer && $item->url && '#' !== $item->url ) {
		$class = 'brokers' === $type ? 'link-strong' : 'mega-menu__link';
		echo '<a class="' . esc_attr( $class ) . '"' . fxt_tt_item_attrs( $item ) . '>' . esc_html( str_replace( '%d', (string) $count, $footer ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper.
	}
	echo '</div>';
}

/**
 * Featured card.
 *
 * @param WP_Post $item Menu item (description = label, title = heading, attr_title = call to action).
 */
function fxt_tt_the_mega_feature( $item ) {
	?>
	<a class="mega-menu__feature placeholder"<?php echo fxt_tt_item_attrs( $item ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php if ( $item->description ) : ?>
			<span class="u-label"><?php echo esc_html( $item->description ); ?></span>
		<?php endif; ?>
		<span class="mega-menu__feature-title"><?php echo esc_html( $item->title ); ?></span>
		<?php if ( $item->attr_title ) : ?>
			<span class="link-strong"><?php echo esc_html( $item->attr_title ); ?></span>
		<?php endif; ?>
	</a>
	<?php
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
