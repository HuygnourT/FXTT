<?php
/**
 * Country entity: the full ISO 3166-1 list as `fxt_market` terms.
 *
 * One term per country is the single source for the country selector, broker
 * availability, test evidence, the directory and country links. The plugin
 * seeds every country once (and adds countries missing after an update); it
 * never renames or deletes a term, so editors can rename a country or mark
 * it as researched under Broker Reviews > Countries.
 *
 * Source: data/countries.json, from the world-countries dataset (ODbL, see
 * data/LICENSE-countries-ODbL.md).
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Country seeding and admin tools.
 */
final class Countries {

	/** Bump when data/countries.json changes so existing sites pick up new rows. */
	const DATA_VERSION = '2026.1';
	const OPTION       = 'fxt_countries_version';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_sync' ) );
		add_action( 'admin_post_fxt_sync_countries', array( __CLASS__, 'handle_sync' ) );
		add_action( 'after-' . Content_Types::MARKET . '-table', array( __CLASS__, 'sync_button' ) );
	}

	/**
	 * Rows from the data file: [ code, name, currency ].
	 *
	 * @return array[]
	 */
	public static function data() {
		$file = FXT_CORE_DIR . 'data/countries.json';
		$rows = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Seed once per data version (admin requests only).
	 */
	public static function maybe_sync() {
		if ( self::DATA_VERSION !== get_option( self::OPTION ) && current_user_can( 'manage_categories' ) ) {
			self::sync();
		}
	}

	/**
	 * Add every country that has no term yet (matched by ISO code).
	 *
	 * @return int Number of countries added.
	 */
	public static function sync() {
		$existing = array();
		foreach ( Repository::markets() as $market ) {
			$existing[ $market['code'] ] = true;
		}
		$added = 0;
		foreach ( self::data() as $row ) {
			list( $code, $name, $currency ) = array_pad( (array) $row, 3, '' );
			$code = strtoupper( (string) $code );
			if ( ! preg_match( '/^[A-Z]{2}$/', $code ) || isset( $existing[ $code ] ) ) {
				continue;
			}
			$slug = strtolower( $code );
			$term = get_term_by( 'slug', $slug, Content_Types::MARKET );
			if ( ! $term ) {
				$result = wp_insert_term( $name, Content_Types::MARKET, array( 'slug' => $slug ) );
				if ( is_wp_error( $result ) ) {
					continue;
				}
				$term_id = (int) $result['term_id'];
				++$added;
			} else {
				$term_id = (int) $term->term_id;
			}
			update_term_meta( $term_id, 'fxt_iso', $code );
			if ( $currency && ! get_term_meta( $term_id, 'fxt_currency', true ) ) {
				update_term_meta( $term_id, 'fxt_currency', $currency );
			}
		}
		update_option( self::OPTION, self::DATA_VERSION, false );
		Repository::flush();
		return $added;
	}

	/**
	 * "Add missing countries" button under the Countries table.
	 */
	public static function sync_button() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		printf(
			'<form method="post" action="%s" style="margin-top:12px">%s<input type="hidden" name="action" value="fxt_sync_countries"><button class="button" type="submit">%s</button> <span class="description">%s</span></form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			wp_nonce_field( 'fxt_sync_countries', '_wpnonce', true, false ),
			esc_html__( 'Add missing countries', 'fxt-core' ),
			esc_html__( 'Restores any country from the ISO 3166 list that was deleted. Existing names and research status are kept.', 'fxt-core' )
		);
	}

	/**
	 * Button handler.
	 */
	public static function handle_sync() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'fxt-core' ), 403 );
		}
		check_admin_referer( 'fxt_sync_countries' );
		$added = self::sync();
		wp_safe_redirect( add_query_arg( array( 'taxonomy' => Content_Types::MARKET, 'post_type' => Content_Types::BROKER, 'fxt_added' => $added ), admin_url( 'edit-tags.php' ) ) );
		exit;
	}
}
