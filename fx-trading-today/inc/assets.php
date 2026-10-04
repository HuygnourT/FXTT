<?php
/**
 * Styles, scripts, font preloads and the icon sprite.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stylesheets in cascade order. Fonts are declared in theme.json (fontFace),
 * so WordPress prints the @font-face rules for the front end and the editor.
 *
 * @param bool $relative Return paths relative to the theme (editor styles).
 * @return string[]
 */
function fxt_tt_stylesheets( $relative = false ) {
	$files = array( 'tokens', 'base', 'components', 'layout', 'sections', 'pages', 'blocks' );
	return array_map(
		static function ( $file ) use ( $relative ) {
			return ( $relative ? '' : FXT_TT_URI . '/' ) . 'assets/css/' . $file . '.css';
		},
		$files
	);
}

/**
 * Front-end assets.
 */
function fxt_tt_enqueue() {
	$previous = array( 'global-styles' );
	foreach ( fxt_tt_stylesheets() as $url ) {
		$handle = 'fxt-tt-' . basename( $url, '.css' );
		wp_enqueue_style( $handle, $url, $previous, FXT_TT_VERSION );
		$previous = array( $handle );
	}

	$args = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);
	wp_enqueue_script( 'fxt-tt-navigation', FXT_TT_URI . '/assets/js/navigation.js', array(), FXT_TT_VERSION, $args );

	if ( fxt_tt_has_toc() ) {
		wp_enqueue_script( 'fxt-tt-toc', FXT_TT_URI . '/assets/js/toc.js', array(), FXT_TT_VERSION, $args );
	}
}
add_action( 'wp_enqueue_scripts', 'fxt_tt_enqueue' );

/**
 * Pages that render a table of contents.
 *
 * @return bool
 */
function fxt_tt_has_toc() {
	return is_singular( array( 'fxt_broker', 'post' ) ) || is_page_template( 'templates/page-document.php' );
}

/**
 * Preload the two faces used above the fold.
 */
function fxt_tt_preload_fonts() {
	foreach ( array( 'ibm-plex-sans-latin-400-normal', 'newsreader-latin-500-normal' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( FXT_TT_URI . '/assets/fonts/' . $font . '.woff2' )
		);
	}
}
add_action( 'wp_head', 'fxt_tt_preload_fonts', 1 );

/**
 * Theme colour for mobile browser chrome.
 */
function fxt_tt_meta_theme_color() {
	echo '<meta name="theme-color" content="#f5f3ee">' . "\n";
}
add_action( 'wp_head', 'fxt_tt_meta_theme_color', 2 );

/**
 * Inline SVG sprite (referenced by <use href="#i-name">).
 */
function fxt_tt_sprite() {
	$file = FXT_TT_DIR . '/assets/icons.svg';
	if ( is_readable( $file ) ) {
		echo file_get_contents( $file ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions -- static theme asset.
	}
}
add_action( 'wp_body_open', 'fxt_tt_sprite', 1 );

/**
 * Block editor: the editor iframe needs the sprite too (dynamic block previews).
 */
function fxt_tt_editor_assets() {
	$file = FXT_TT_DIR . '/assets/icons.svg';
	if ( ! is_readable( $file ) ) {
		return;
	}
	$sprite = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- static theme asset.
	wp_register_script( 'fxt-tt-editor', '', array(), FXT_TT_VERSION, true );
	wp_enqueue_script( 'fxt-tt-editor' );
	wp_add_inline_script(
		'fxt-tt-editor',
		'(function(s){function add(doc){if(doc&&!doc.getElementById("fxt-sprite")){var d=doc.createElement("div");d.id="fxt-sprite";d.hidden=true;d.innerHTML=s;doc.body.appendChild(d);}}add(document);new MutationObserver(function(){var f=document.querySelector("iframe[name=editor-canvas]");if(f&&f.contentDocument&&f.contentDocument.body){add(f.contentDocument);}}).observe(document.body,{childList:true,subtree:true});})(' . wp_json_encode( $sprite ) . ');'
	);
}
add_action( 'enqueue_block_editor_assets', 'fxt_tt_editor_assets' );
