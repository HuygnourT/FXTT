<?php
/**
 * FX Trading Today theme bootstrap.
 *
 * The theme owns presentation only: layout, templates, styles, patterns and
 * block styles. Broker data, blocks and the demo importer live in the
 * FX Trading Today Core plugin so content survives a theme change.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

define( 'FXT_TT_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'FXT_TT_DIR', get_template_directory() );
define( 'FXT_TT_URI', get_template_directory_uri() );

require FXT_TT_DIR . '/inc/setup.php';
require FXT_TT_DIR . '/inc/assets.php';
require FXT_TT_DIR . '/inc/block-styles.php';
require FXT_TT_DIR . '/inc/template-tags.php';
require FXT_TT_DIR . '/inc/navigation.php';
require FXT_TT_DIR . '/inc/compat.php';
