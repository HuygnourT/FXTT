<?php
/**
 * Behaviour when the companion plugin is missing.
 *
 * The theme still renders posts and pages without FX Trading Today Core, but
 * broker data, the country picker and the demo importer come from the plugin.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin notice asking to install/activate the plugin.
 */
function fxt_tt_plugin_notice() {
	if ( fxt_tt_core() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$installed = file_exists( WP_PLUGIN_DIR . '/fxt-core/fxt-core.php' );
	$url       = $installed
		? wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( 'fxt-core/fxt-core.php' ) ), 'activate-plugin_fxt-core/fxt-core.php' )
		: admin_url( 'plugin-install.php?tab=upload' );
	printf(
		'<div class="notice notice-warning"><p>%s <a class="button button-primary" href="%s">%s</a></p></div>',
		esc_html__( 'FX Trading Today works with the FX Trading Today Core plugin, which provides broker reviews, test evidence, blocks and the demo importer.', 'fx-trading-today' ),
		esc_url( $url ),
		$installed ? esc_html__( 'Activate plugin', 'fx-trading-today' ) : esc_html__( 'Upload plugin', 'fx-trading-today' )
	);
}
add_action( 'admin_notices', 'fxt_tt_plugin_notice' );
