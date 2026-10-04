<?php
/**
 * Frontend scripts and the country picker dialog.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Asset registration. Block view scripts are enqueued by WordPress only on
 * pages that contain the block; country.js loads everywhere because the
 * header country chip is on every page.
 */
final class Assets {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'dialog' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'sprite' ), 1 );
	}

	/**
	 * Icon sprite used by fxt_core_icon(). A theme that prints its own sprite
	 * declares add_theme_support( 'fxt-icons' ) and this is skipped.
	 */
	public static function sprite() {
		if ( current_theme_supports( 'fxt-icons' ) ) {
			return;
		}
		$file = FXT_CORE_DIR . 'assets/icons.svg';
		if ( is_readable( $file ) ) {
			echo file_get_contents( $file ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions -- static, trusted plugin asset.
		}
	}

	/**
	 * Register view scripts (called on init, before blocks reference them).
	 */
	public static function register_frontend_scripts() {
		$args = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);
		$js   = FXT_CORE_URL . 'assets/js/';
		wp_register_script( 'fxt-core-country', $js . 'country.js', array(), FXT_CORE_VERSION, $args );
		wp_register_script( 'fxt-core-compare', $js . 'compare.js', array( 'fxt-core-country' ), FXT_CORE_VERSION, $args );
		wp_register_script( 'fxt-core-directory', $js . 'directory.js', array( 'fxt-core-country' ), FXT_CORE_VERSION, $args );
		wp_register_script( 'fxt-core-evidence', $js . 'evidence.js', array(), FXT_CORE_VERSION, $args );
	}

	/**
	 * Enqueue the country script with its configuration.
	 */
	public static function enqueue() {
		if ( ! Repository::markets() ) {
			return;
		}
		$config = array(
			'renderUrl' => esc_url_raw( rest_url( 'fxt/v1/render' ) ),
			'cookie'    => Repository::COOKIE,
			'current'   => Repository::current_market(),
			// Pages whose whole body depends on the country reload instead of refreshing regions.
			'reload'    => is_singular( array( Content_Types::BROKER, Content_Types::EVIDENCE ) ),
		);
		wp_enqueue_script( 'fxt-core-country' );
		wp_add_inline_script( 'fxt-core-country', 'window.fxtCore = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * Country dialog markup.
	 */
	public static function dialog() {
		if ( wp_script_is( 'fxt-core-country', 'enqueued' ) ) {
			fxt_core_view( 'country/dialog' );
		}
	}
}
