<?php
/**
 * Meta description of broker reviews (1.4.6).
 *
 * The description is written per review (box "Review: meta description")
 * and is independent of the visible content: nothing is generated from the
 * General summary, the excerpt or the body. When an SEO plugin manages meta
 * descriptions, its own tag is kept and this plugin prints none; the saved
 * text is offered to the SEO plugin only when it has no description for
 * that page.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Meta description output.
 */
class Seo {

	const META = 'fxt_meta_description';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_tag' ), 1 );
		// Fallbacks for SEO plugins: used only when the plugin has no description of its own.
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'fallback' ), 20 );                // Yoast SEO.
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'fallback' ), 20 ); // Rank Math.
		add_filter( 'aioseo_description', array( __CLASS__, 'fallback' ), 20 );             // All in One SEO.
		add_filter( 'seopress_titles_desc', array( __CLASS__, 'fallback' ), 20 );           // SEOPress.
	}

	/**
	 * Saved description of a broker review, as one line of plain text.
	 *
	 * @param int|\WP_Post|null $post Post (default: current).
	 * @return string
	 */
	public static function description( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || Content_Types::BROKER !== $post->post_type ) {
			return '';
		}
		$text = wp_strip_all_tags( (string) get_post_meta( $post->ID, self::META, true ) );
		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
	}

	/**
	 * Whether an SEO plugin manages meta descriptions on this site.
	 *
	 * @return bool
	 */
	public static function seo_plugin_active() {
		$active = defined( 'WPSEO_VERSION' )            // Yoast SEO.
			|| defined( 'RANK_MATH_VERSION' )            // Rank Math.
			|| defined( 'AIOSEO_VERSION' )               // All in One SEO.
			|| defined( 'SEOPRESS_VERSION' )             // SEOPress.
			|| defined( 'THE_SEO_FRAMEWORK_VERSION' )    // The SEO Framework.
			|| defined( 'SLIM_SEO_VER' )                 // Slim SEO.
			|| defined( 'SQ_VERSION' );                  // Squirrly SEO.
		/**
		 * Filters whether another plugin outputs the meta description, in which
		 * case FX Trading Today Core prints none.
		 *
		 * @param bool $active Detected.
		 */
		return (bool) apply_filters( 'fxt_core_seo_plugin_active', $active );
	}

	/**
	 * Print <meta name="description"> on a broker review with a saved
	 * description, unless an SEO plugin handles it.
	 */
	public static function print_tag() {
		if ( ! is_singular( Content_Types::BROKER ) || self::seo_plugin_active() ) {
			return;
		}
		$text = self::description( get_queried_object_id() );
		if ( '' === $text ) {
			return;
		}
		echo '<meta name="description" content="' . esc_attr( $text ) . '">' . "\n";
	}

	/**
	 * SEO plugin filter: keep the plugin's description; offer ours when empty.
	 *
	 * @param mixed $description Description from the SEO plugin.
	 * @return mixed
	 */
	public static function fallback( $description ) {
		if ( ( is_string( $description ) && '' !== trim( $description ) ) || ! is_singular( Content_Types::BROKER ) ) {
			return $description;
		}
		$text = self::description( get_queried_object_id() );
		return '' !== $text ? $text : $description;
	}
}
