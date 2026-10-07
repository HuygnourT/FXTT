<?php
/**
 * Plugin Name:       FX Trading Today Core
 * Description:       Broker reviews, test evidence, country data, blocks and demo import for the FX Trading Today theme. Content and data live here so they survive a theme change.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            FX Trading Today
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fxt-core
 * Domain Path:       /languages
 *
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'FXT_CORE_VERSION', '1.1.0' );
define( 'FXT_CORE_FILE', __FILE__ );
define( 'FXT_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FXT_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once FXT_CORE_DIR . 'includes/schema.php';
require_once FXT_CORE_DIR . 'includes/class-fields.php';
require_once FXT_CORE_DIR . 'includes/class-block-templates.php';
require_once FXT_CORE_DIR . 'includes/class-content-types.php';
require_once FXT_CORE_DIR . 'includes/class-repository.php';
require_once FXT_CORE_DIR . 'includes/class-countries.php';
require_once FXT_CORE_DIR . 'includes/functions.php';
require_once FXT_CORE_DIR . 'includes/class-blocks.php';
require_once FXT_CORE_DIR . 'includes/class-assets.php';
require_once FXT_CORE_DIR . 'includes/class-rest.php';
require_once FXT_CORE_DIR . 'includes/class-admin.php';
require_once FXT_CORE_DIR . 'includes/class-importer.php';

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'fxt-core', false, dirname( plugin_basename( FXT_CORE_FILE ) ) . '/languages' );

		FXT\Core\Content_Types::init();
		FXT\Core\Repository::init();
		FXT\Core\Countries::init();
		FXT\Core\Blocks::init();
		FXT\Core\Assets::init();
		FXT\Core\Rest::init();
		FXT\Core\Importer::init();
		if ( is_admin() ) {
			FXT\Core\Admin::init();
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once FXT_CORE_DIR . 'includes/class-cli.php';
			WP_CLI::add_command( 'fxt demo', 'FXT\Core\CLI' );
		}
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		FXT\Core\Content_Types::register_post_types();
		FXT\Core\Content_Types::register_taxonomies();
		FXT\Core\Countries::sync();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
