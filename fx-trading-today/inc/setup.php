<?php
/**
 * Theme supports, menus, pattern categories.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme features.
 */
function fxt_tt_setup() {
	load_theme_textdomain( 'fx-trading-today', FXT_TT_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 48, 'width' => 200, 'flex-width' => true, 'flex-height' => true ) );

	// The theme prints its own icon sprite; the plugin skips its fallback copy.
	add_theme_support( 'fxt-icons' );

	// Only this theme's patterns: core patterns do not use the design system.
	remove_theme_support( 'core-block-patterns' );

	add_theme_support( 'editor-styles' );
	add_editor_style( fxt_tt_stylesheets( true ) );

	add_post_type_support( 'page', 'excerpt' );

	register_nav_menus(
		array(
			'primary'  => __( 'Primary (header and mobile menu)', 'fx-trading-today' ),
			'footer-1' => __( 'Footer column 1', 'fx-trading-today' ),
			'footer-2' => __( 'Footer column 2', 'fx-trading-today' ),
			'footer-3' => __( 'Footer column 3', 'fx-trading-today' ),
			'footer-4' => __( 'Footer column 4', 'fx-trading-today' ),
			'footer-5' => __( 'Footer column 5', 'fx-trading-today' ),
		)
	);
}
add_action( 'after_setup_theme', 'fxt_tt_setup' );

/**
 * Pattern categories (patterns themselves are files in /patterns).
 */
function fxt_tt_pattern_categories() {
	register_block_pattern_category( 'fxt-home', array( 'label' => __( 'FX Trading Today: home', 'fx-trading-today' ) ) );
	register_block_pattern_category( 'fxt-sections', array( 'label' => __( 'FX Trading Today: sections', 'fx-trading-today' ) ) );
}
add_action( 'init', 'fxt_tt_pattern_categories' );

/**
 * Do not load remote patterns from the directory.
 */
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Body classes used by the stylesheet.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function fxt_tt_body_class( $classes ) {
	if ( fxt_tt_banner_text() ) {
		$classes[] = 'has-site-banner';
	}
	return $classes;
}
add_filter( 'body_class', 'fxt_tt_body_class' );

/**
 * Shorter excerpts for cards.
 *
 * @return int
 */
function fxt_tt_excerpt_length() {
	return 32;
}
add_filter( 'excerpt_length', 'fxt_tt_excerpt_length' );
