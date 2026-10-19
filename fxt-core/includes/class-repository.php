<?php
/**
 * Read model: the only place that turns posts, terms, meta and options into
 * the plain arrays templates and blocks render. Brokers are cached in a
 * transient that is flushed whenever any broker data changes.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Data access layer.
 */
final class Repository {

	const CACHE_KEY     = 'fxt_core_brokers_v4'; // v4: broker name, account fields.
	const COOKIE        = 'fxt_country';
	const SETTINGS      = 'fxt_settings';

	/** @var array|null Per-request market cache. */
	private static $markets = null;

	/** @var array|null Per-request broker cache. */
	private static $brokers = null;

	/** @var array|null Per-request test statistics (see test_stats()). */
	private static $test_stats = null;

	/**
	 * Cache invalidation hooks.
	 */
	public static function init() {
		$flush = array( __CLASS__, 'flush' );
		add_action( 'save_post_' . Content_Types::BROKER, $flush );
		add_action( 'save_post_' . Content_Types::EVIDENCE, $flush );
		add_action( 'deleted_post', $flush );
		add_action( 'trashed_post', $flush );
		add_action( 'set_object_terms', $flush );
		add_action( 'saved_term', $flush );
		add_action( 'delete_term', $flush );
		add_action( 'created_' . Content_Types::MARKET, $flush );
		add_action( 'edited_' . Content_Types::MARKET, $flush );
		add_action( 'delete_' . Content_Types::MARKET, $flush );
		add_action( 'profile_update', $flush );
		add_action( 'updated_option', array( __CLASS__, 'maybe_flush_option' ) );
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta', 'added_term_meta', 'updated_term_meta', 'deleted_term_meta', 'added_user_meta', 'updated_user_meta' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'maybe_flush_meta' ), 10, 3 );
		}
	}

	/**
	 * Flush caches.
	 */
	public static function flush() {
		delete_transient( self::CACHE_KEY );
		delete_transient( 'fxt_core_brokers_v1' ); // Left by older versions.
		delete_transient( 'fxt_core_brokers_v2' );
		self::$brokers      = null;
		self::$markets      = null;
		self::$market_index = null;
		self::$term_lists   = array();
		self::$test_stats   = null;
	}

	/**
	 * Flush when one of our meta keys changes (block editor saves meta after save_post).
	 *
	 * @param mixed  $meta_ids  Unused.
	 * @param int    $object_id Unused.
	 * @param string $meta_key  Meta key.
	 */
	public static function maybe_flush_meta( $meta_ids, $object_id, $meta_key ) {
		if ( 0 === strpos( (string) $meta_key, 'fxt_' ) ) {
			self::flush();
		}
	}

	/**
	 * Flush when settings change (weights affect rendering).
	 *
	 * @param string $option Option name.
	 */
	public static function maybe_flush_option( $option ) {
		if ( self::SETTINGS === $option ) {
			self::flush();
		}
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * All settings merged with defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$defaults = Schema::settings_defaults();
		$saved    = get_option( self::SETTINGS, array() );
		$saved    = is_array( $saved ) ? $saved : array();
		$settings = array_merge( $defaults, array_filter( $saved, static function ( $value ) {
			return null !== $value;
		} ) );
		$settings['pages'] = array_merge( $defaults['pages'], is_array( $settings['pages'] ) ? array_filter( $settings['pages'], 'is_numeric' ) : array() );
		if ( empty( $settings['score_categories'] ) || ! is_array( $settings['score_categories'] ) ) {
			$settings['score_categories'] = $defaults['score_categories'];
			// Sites saved before categories became editable kept weights only.
			if ( ! empty( $saved['weights'] ) && is_array( $saved['weights'] ) ) {
				foreach ( $settings['score_categories'] as $i => $category ) {
					if ( isset( $saved['weights'][ $category['key'] ] ) ) {
						$settings['score_categories'][ $i ]['weight'] = (int) $saved['weights'][ $category['key'] ];
					}
				}
			}
		}
		foreach ( array( 'directory_options', 'directory_ui', 'country_context' ) as $group ) {
			// Empty fields fall back to the default wording, field by field.
			$values             = isset( $saved[ $group ] ) && is_array( $saved[ $group ] ) ? $saved[ $group ] : array();
			$settings[ $group ] = array_merge(
				$defaults[ $group ],
				array_filter(
					$values,
					static function ( $value ) {
						return null !== $value && '' !== $value;
					}
				)
			);
		}
		$settings['directory_filters'] = self::normalize_directory_filters( isset( $saved['directory_filters'] ) ? $saved['directory_filters'] : null );
		return $settings;
	}

	/**
	 * Directory filter rows: known keys only, each once, in the saved order.
	 * Keys missing from the saved list are appended with default wording, so
	 * a filter can only be hidden on purpose (the "Hide" checkbox).
	 *
	 * @param mixed $rows Saved rows.
	 * @return array[] Each: key, label, placeholder, description, hidden.
	 */
	public static function normalize_directory_filters( $rows ) {
		$defaults = array();
		foreach ( Schema::default_directory_filters() as $row ) {
			$defaults[ $row['key'] ] = $row;
		}
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$key = isset( $row['key'] ) ? (string) $row['key'] : '';
			if ( ! isset( $defaults[ $key ] ) || isset( $out[ $key ] ) ) {
				continue;
			}
			$clean = $defaults[ $key ];
			foreach ( array( 'label', 'placeholder', 'description' ) as $field ) {
				if ( isset( $row[ $field ] ) && '' !== (string) $row[ $field ] ) {
					$clean[ $field ] = (string) $row[ $field ];
				}
			}
			$clean['hidden'] = ! empty( $row['hidden'] );
			$out[ $key ]     = $clean;
		}
		foreach ( $defaults as $key => $row ) {
			if ( ! isset( $out[ $key ] ) ) {
				$out[ $key ] = $row;
			}
		}
		return array_values( $out );
	}

	/**
	 * One text of the Broker Reviews page with {count} and {country} filled in.
	 * Values are plain text; callers escape on output.
	 *
	 * @param string $key  Key of directory_ui.
	 * @param array  $vars Placeholder values: count, country.
	 * @return string
	 */
	public static function ui_text( $key, array $vars = array() ) {
		$ui   = self::setting( 'directory_ui' );
		$text = isset( $ui[ $key ] ) ? (string) $ui[ $key ] : '';
		$map  = array();
		foreach ( array( 'count', 'country' ) as $name ) {
			if ( isset( $vars[ $name ] ) ) {
				$map[ '{' . $name . '}' ] = (string) $vars[ $name ];
			}
		}
		return wp_strip_all_tags( strtr( $text, $map ) );
	}

	/**
	 * Score categories in display order (single source for every score view).
	 *
	 * @return array[] Each: key, label, weight, description, evidence.
	 */
	public static function categories() {
		$out = array();
		foreach ( (array) self::setting( 'score_categories' ) as $row ) {
			if ( empty( $row['key'] ) ) {
				continue;
			}
			$out[] = array(
				'key'         => (string) $row['key'],
				'label'       => isset( $row['label'] ) ? (string) $row['label'] : (string) $row['key'],
				'weight'      => isset( $row['weight'] ) ? (int) $row['weight'] : 0,
				'description' => isset( $row['description'] ) ? (string) $row['description'] : '',
				'evidence'    => isset( $row['evidence'] ) ? (string) $row['evidence'] : '',
			);
		}
		return $out;
	}

	/**
	 * Overall score from category scores x weights, or null while any
	 * weighted category is unscored.
	 *
	 * @param array $breakdown Category key => score.
	 * @return float|null
	 */
	public static function weighted_score( array $breakdown ) {
		$total  = 0.0;
		$weight = 0;
		foreach ( self::categories() as $category ) {
			if ( $category['weight'] <= 0 ) {
				continue;
			}
			if ( ! isset( $breakdown[ $category['key'] ] ) || ! is_numeric( $breakdown[ $category['key'] ] ) ) {
				return null;
			}
			$total  += (float) $breakdown[ $category['key'] ] * $category['weight'];
			$weight += $category['weight'];
		}
		return $weight ? $total / $weight : null;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function setting( $key ) {
		$settings = self::settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * URL of a key page from settings, or '' when not configured.
	 *
	 * @param string $key directory|compare|evidence|methodology.
	 * @return string
	 */
	public static function page_url( $key ) {
		$pages = self::setting( 'pages' );
		$id    = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
		return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : '';
	}

	/* ---------------------------------------------------------------------
	 * Markets
	 * ------------------------------------------------------------------ */

	/** @var array<string,int>|null Index of markets() by ISO code. */
	private static $market_index = null;

	/**
	 * All countries, A to Z by name.
	 *
	 * @return array[] Each: code, name, currency, status ('' = not researched), term_id.
	 */
	public static function markets() {
		if ( null !== self::$markets ) {
			return self::$markets;
		}
		$terms   = get_terms(
			array(
				'taxonomy'   => Content_Types::MARKET,
				'hide_empty' => false,
			)
		);
		$markets = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$code      = strtoupper( (string) get_term_meta( $term->term_id, 'fxt_iso', true ) );
				$code      = $code ? $code : strtoupper( $term->slug );
				$markets[] = array(
					'code'     => $code,
					'name'     => $term->name,
					'currency' => (string) get_term_meta( $term->term_id, 'fxt_currency', true ),
					'status'   => (string) get_term_meta( $term->term_id, 'fxt_status', true ),
					'term_id'  => $term->term_id,
				);
			}
		}
		// Alphabetical by name; accents are ignored so "Åland Islands" sorts under A.
		usort(
			$markets,
			static function ( $a, $b ) {
				return strcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) );
			}
		);
		self::$markets      = $markets;
		self::$market_index = array();
		foreach ( $markets as $i => $market ) {
			self::$market_index[ $market['code'] ] = $i;
		}
		return $markets;
	}

	/** @var array<string, array[]> Per-request term lists. */
	private static $term_lists = array();

	/**
	 * All terms of a classification taxonomy (platforms, account types,
	 * regulators) in creation order: the single list every table, tile and
	 * filter renders from.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array[] Each: slug, name, full_name, compact, term_id.
	 */
	public static function terms( $taxonomy ) {
		if ( isset( self::$term_lists[ $taxonomy ] ) ) {
			return self::$term_lists[ $taxonomy ];
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'term_id',
			)
		);
		$out   = array();
		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			$full  = (string) get_term_meta( $term->term_id, 'fxt_full_name', true );
			$out[] = array(
				'slug'      => $term->slug,
				'name'      => $term->name,
				'full_name' => $full ? $full : $term->name,
				'compact'   => (bool) get_term_meta( $term->term_id, 'fxt_compact', true ),
				'term_id'   => $term->term_id,
			);
		}
		self::$term_lists[ $taxonomy ] = $out;
		return $out;
	}

	/**
	 * Countries with research published, in progress or planned (A to Z).
	 * Used for "N of M countries researched" and for country shortcuts.
	 *
	 * @return array[]
	 */
	public static function researched_markets() {
		return array_values(
			array_filter(
				self::markets(),
				static function ( $market ) {
					return '' !== $market['status'];
				}
			)
		);
	}

	/**
	 * One market by ISO code.
	 *
	 * @param string $code ISO code.
	 * @return array|null
	 */
	public static function market( $code ) {
		$markets = self::markets();
		$code    = strtoupper( (string) $code );
		return isset( self::$market_index[ $code ] ) ? $markets[ self::$market_index[ $code ] ] : null;
	}

	/**
	 * Default market from settings (falls back to the first market).
	 *
	 * @return string ISO code or ''.
	 */
	public static function default_market() {
		$code = (string) self::setting( 'default_market' );
		if ( self::market( $code ) ) {
			return $code;
		}
		$markets = self::markets();
		return $markets ? $markets[0]['code'] : '';
	}

	/**
	 * The visitor's market: ?country= wins, then the preference cookie, then the default.
	 * No geolocation is performed; the cookie only stores an explicit choice.
	 *
	 * @return string ISO code.
	 */
	public static function current_market() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display preference.
		$candidates = array(
			isset( $_GET['country'] ) ? sanitize_key( wp_unslash( $_GET['country'] ) ) : '',
			isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '',
		);
		foreach ( $candidates as $code ) {
			if ( $code && self::market( $code ) ) {
				return strtoupper( $code );
			}
		}
		return self::default_market();
	}

	/**
	 * Whether the current market came from an explicit visitor choice.
	 *
	 * @return bool
	 */
	public static function market_is_chosen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['country'] ) || isset( $_COOKIE[ self::COOKIE ] );
	}

	/* ---------------------------------------------------------------------
	 * Brokers
	 * ------------------------------------------------------------------ */

	/**
	 * All published brokers, normalized, sorted by score (highest first, pending last).
	 *
	 * @return array[]
	 */
	public static function brokers() {
		if ( null !== self::$brokers ) {
			return self::$brokers;
		}
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			self::$brokers = $cached;
			return $cached;
		}

		$posts   = get_posts(
			array(
				'post_type'      => Content_Types::BROKER,
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'no_found_rows'  => true,
				// Ties in score keep this order (usort is stable since PHP 8).
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);
		$brokers = array_map( array( __CLASS__, 'normalize_broker' ), $posts );
		usort(
			$brokers,
			static function ( $a, $b ) {
				return ( null === $b['score'] ? -1 : $b['score'] ) <=> ( null === $a['score'] ? -1 : $a['score'] );
			}
		);

		set_transient( self::CACHE_KEY, $brokers, DAY_IN_SECONDS );
		self::$brokers = $brokers;
		return $brokers;
	}

	/**
	 * One broker by post ID (published brokers come from cache; others are normalized live).
	 *
	 * @param int|\WP_Post $post Post or ID.
	 * @return array|null
	 */
	public static function broker( $post ) {
		$post = get_post( $post );
		if ( ! $post || Content_Types::BROKER !== $post->post_type ) {
			return null;
		}
		if ( 'publish' === $post->post_status && ! is_preview() ) {
			foreach ( self::brokers() as $broker ) {
				if ( $broker['id'] === $post->ID ) {
					return $broker;
				}
			}
		}
		return self::normalize_broker( $post );
	}

	/**
	 * Brokers by a list of slugs (keeps the requested order).
	 *
	 * @param string[] $slugs Post slugs.
	 * @return array[]
	 */
	public static function brokers_by_slug( array $slugs ) {
		$by_slug = array();
		foreach ( self::brokers() as $broker ) {
			$by_slug[ $broker['slug'] ] = $broker;
		}
		$out = array();
		foreach ( $slugs as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$out[] = $by_slug[ $slug ];
			}
		}
		return $out;
	}

	/**
	 * Turn a broker post into a plain array.
	 *
	 * @param \WP_Post $post Broker post.
	 * @return array
	 */
	public static function normalize_broker( \WP_Post $post ) {
		$meta = static function ( $key, $default = null ) use ( $post ) {
			$value = get_post_meta( $post->ID, $key, true );
			return ( '' === $value || null === $value || array() === $value ) ? $default : $value;
		};

		$terms = static function ( $taxonomy ) use ( $post ) {
			// Assignment order (term_order), e.g. the brand's main regulator first.
			$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'orderby' => 'term_order' ) );
			$out   = array();
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[ $term->slug ] = $term->name;
				}
			}
			return $out;
		};

		$title = get_the_title( $post );
		$name  = self::broker_name( (string) $meta( 'fxt_name', '' ), $title );
		$score = $meta( 'fxt_score' );

		return array(
			'id'              => $post->ID,
			'slug'            => $post->post_name,
			'menu_order'      => (int) $post->menu_order,
			'name'            => $name,
			'title'           => $title,
			'url'             => get_permalink( $post ),
			'excerpt'         => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
			'logo_id'         => (int) get_post_thumbnail_id( $post ),
			'monogram'        => $meta( 'fxt_monogram', strtoupper( mb_substr( $name, 0, 2 ) ) ),
			'author_id'       => (int) $post->post_author,
			'status'          => $meta( 'fxt_status', 'published' ),
			// Manual override wins; otherwise the score follows the category weights.
			'score'           => null !== $score ? (float) $score : self::weighted_score( (array) $meta( 'fxt_score_breakdown', array() ) ),
			'score_source'    => null !== $score ? 'manual' : 'calculated',
			'breakdown'       => $meta( 'fxt_score_breakdown', array() ),
			'reviewed'        => $meta( 'fxt_reviewed', '' ),
			'founded'         => $meta( 'fxt_founded', '' ),
			'min_deposit'     => null === $meta( 'fxt_min_deposit' ) ? null : (float) $meta( 'fxt_min_deposit' ),
			'currencies'      => $meta( 'fxt_currencies', array() ),
			'other_platforms' => $meta( 'fxt_other_platforms', '' ),
			'trading'         => wp_parse_args( $meta( 'fxt_trading', array() ), array( 'leverage' => '', 'execution' => '', 'order_types' => '' ) ),
			'costs'           => wp_parse_args( $meta( 'fxt_costs', array() ), array( 'spread' => '', 'commission' => '', 'swap' => '', 'other_fees' => '' ) ),
			'payments'        => wp_parse_args( $meta( 'fxt_payments', array() ), array( 'bank' => false, 'cards' => false, 'ewallets' => false, 'crypto' => false, 'time_local' => '', 'time_cards' => '', 'time_ewallets' => '', 'time_crypto' => '' ) ),
			'accounts'        => Accounts::rows( $post->ID ),
			'account_fields'  => Accounts::fields( $post->ID ),
			'account_display' => Accounts::display( $post->ID ),
			'entities'        => array_map( array( __CLASS__, 'normalize_entity' ), (array) $meta( 'fxt_entities', array() ) ),
			'sources'         => (array) $meta( 'fxt_sources', array() ),
			'markets'         => $meta( 'fxt_markets', array() ),
			'accepting'       => self::accepting_config( $post->ID ),
			'affiliate_url'   => $meta( 'fxt_affiliate_url', '' ),
			'cta_label'       => $meta( 'fxt_cta_label', '' ),
			'regulators'      => array_values( $terms( Content_Types::REGULATOR ) ),
			'regulator_slugs' => array_keys( $terms( Content_Types::REGULATOR ) ),
			'platforms'       => $terms( Content_Types::PLATFORM ),
			'account_types'   => array_keys( $terms( Content_Types::ACCOUNT_TYPE ) ),
		);
	}

	/**
	 * Broker name shown in buttons, tables and headings: the "Broker name"
	 * field, or the post title without a review suffix ("Exness review 2026:
	 * fees and accounts" -> "Exness") for brokers saved before 1.4.2.
	 *
	 * @param string $field Saved "Broker name".
	 * @param string $title Post title.
	 * @return string
	 */
	public static function broker_name( $field, $title ) {
		$field = trim( wp_strip_all_tags( (string) $field ) );
		if ( '' !== $field ) {
			return $field;
		}
		$title = trim( html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' ) );
		$name  = preg_replace( '/\s+(?:[:|\x{2013}\x{2014}]|-\s).*$/u', '', $title );
		$name  = preg_replace( '/\s+reviews?\b.*$/iu', '', (string) $name );
		$name  = trim( (string) $name );
		return '' !== $name ? $name : $title;
	}

	/**
	 * "Accepting residents" of a broker: the single source of truth for
	 * availability. Brokers saved before version 1.4 kept availability per
	 * country row; until the broker is saved again it is read from there
	 * (countries marked "Available"; the source when all those rows agree).
	 *
	 * @param int $post_id Broker post ID.
	 * @return array { countries: string[], source, ref, checked, configured: bool }
	 */
	public static function accepting_config( $post_id ) {
		// metadata_exists(): a registered meta returns its default (empty array) when never saved.
		$saved = metadata_exists( 'post', $post_id, 'fxt_accepting' ) ? get_post_meta( $post_id, 'fxt_accepting', true ) : null;
		if ( is_array( $saved ) ) {
			$countries = array_values( array_filter( array_map( 'strtoupper', (array) ( isset( $saved['countries'] ) ? $saved['countries'] : array() ) ) ) );
			return array(
				'countries'  => $countries,
				'source'     => isset( $saved['source'] ) ? (string) $saved['source'] : '',
				'ref'        => isset( $saved['ref'] ) ? (string) $saved['ref'] : '',
				'checked'    => isset( $saved['checked'] ) ? (string) $saved['checked'] : '',
				'configured' => (bool) $countries,
			);
		}
		// Migration from version 1.3 country rows.
		$countries = array();
		$sources   = array();
		foreach ( (array) get_post_meta( $post_id, 'fxt_markets', true ) as $code => $row ) {
			if ( is_array( $row ) && isset( $row['availability'] ) && 'yes' === $row['availability'] ) {
				$countries[]                 = strtoupper( (string) $code );
				$sources[ isset( $row['source'] ) ? (string) $row['source'] : '' ] = true;
			}
		}
		return array(
			'countries'  => $countries,
			'source'     => 1 === count( $sources ) ? (string) key( $sources ) : '',
			'ref'        => '',
			'checked'    => '',
			'configured' => (bool) $countries,
		);
	}

	/**
	 * Number of countries whose residents a broker accepts.
	 *
	 * @param array $broker Normalized broker.
	 * @return int
	 */
	public static function accepting_count( array $broker ) {
		return count( $broker['accepting']['countries'] );
	}

	/**
	 * Whether a country is in the broker's Tested residents.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return bool
	 */
	public static function is_tested_resident( array $broker, $code ) {
		return isset( $broker['markets'][ strtoupper( (string) $code ) ] );
	}

	/**
	 * Legal entity row with defaults. Version 1.1 stored a "verified"
	 * checkbox; it reads as source "verified" until the row is saved again.
	 *
	 * @param mixed $entity Saved row.
	 * @return array
	 */
	public static function normalize_entity( $entity ) {
		$entity = array_filter(
			(array) $entity,
			static function ( $v ) {
				return null !== $v;
			}
		);
		$entity = wp_parse_args( $entity, array( 'key' => '', 'name' => '', 'regulator' => '', 'licence' => '', 'jurisdiction' => '', 'protection' => '', 'source' => '', 'source_ref' => '', 'checked' => '' ) );
		if ( '' === (string) $entity['source'] && ! empty( $entity['verified'] ) ) {
			$entity['source'] = 'verified';
		}
		unset( $entity['verified'] );
		return $entity;
	}

	/* ---------------------------------------------------------------------
	 * Data provenance
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve a stored classification into what may be displayed.
	 * "Tested" is shown only when published test evidence backs it;
	 * otherwise the fact is flagged for review instead of passing as tested.
	 *
	 * @param array       $stored    { source, ref, checked }.
	 * @param array       $broker    Normalized broker.
	 * @param string|null $code      Country the fact belongs to (null = any country).
	 * @return array { source: tested|verified|stated|review|'', ref, checked, label }
	 */
	public static function resolve_source( array $stored, array $broker, $code = null ) {
		$labels = Schema::sources();
		$source = isset( $stored['source'] ) ? (string) $stored['source'] : '';
		if ( ! isset( $labels[ $source ] ) ) {
			$source = '';
		}
		if ( 'tested' === $source && ! self::has_published_tests( $broker['id'], $code ) ) {
			$source = 'review';
		}
		$label = 'review' === $source ? __( 'Needs review', 'fxt-core' ) : ( '' === $source ? __( 'Source not classified', 'fxt-core' ) : $labels[ $source ] );
		return array(
			'source'  => $source,
			'ref'     => isset( $stored['ref'] ) ? (string) $stored['ref'] : '',
			'checked' => isset( $stored['checked'] ) ? (string) $stored['checked'] : '',
			'label'   => $label,
		);
	}

	/**
	 * Source of one broker-level fact (see Schema::source_rows()).
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $key    Row key.
	 * @return array See resolve_source().
	 */
	public static function source( array $broker, $key ) {
		$stored = isset( $broker['sources'][ $key ] ) && is_array( $broker['sources'][ $key ] ) ? $broker['sources'][ $key ] : array();
		return self::resolve_source( $stored, $broker );
	}

	/**
	 * Source of the legal entity / regulator / licence serving a country.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array See resolve_source().
	 */
	public static function entity_source( array $broker, $code ) {
		$entity = self::entity( $broker, $code );
		return self::resolve_source( array( 'source' => $entity['source'], 'ref' => $entity['source_ref'], 'checked' => $entity['checked'] ), $broker, $code );
	}

	/**
	 * Source of a broker's availability in a country.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array See resolve_source().
	 */
	public static function availability_source( array $broker, $code ) {
		// One source for the whole Accepting residents list (shown in Quick Facts).
		$accepting = $broker['accepting'];
		return self::resolve_source( array( 'source' => $accepting['source'], 'ref' => $accepting['ref'], 'checked' => $accepting['checked'] ), $broker, $code );
	}

	/* ---------------------------------------------------------------------
	 * Test statistics
	 * ------------------------------------------------------------------ */

	/**
	 * Statistics from published test evidence of published brokers.
	 *
	 * Methodology for "methods tested": a payment method counts once per
	 * broker when at least one deposit or withdrawal test row with status
	 * "Completed" uses it in a published evidence set. Methods are matched
	 * by name (case and spacing ignored), so the same method used for both
	 * a deposit and a withdrawal, or in several countries, counts as one.
	 * Tests in progress, failed tests and broker-stated methods are not
	 * counted.
	 *
	 * @return array broker_id => { methods: string[], markets: string[] }
	 */
	public static function test_stats() {
		if ( null !== self::$test_stats ) {
			return self::$test_stats;
		}
		$published = array();
		foreach ( self::brokers() as $broker ) {
			$published[ $broker['id'] ] = true;
		}
		$stats = array();
		foreach ( self::evidence_index() as $row ) {
			if ( empty( $published[ $row['broker_id'] ] ) ) {
				continue;
			}
			$methods = array();
			foreach ( array( 'fxt_deposits', 'fxt_withdrawals' ) as $key ) {
				foreach ( (array) get_post_meta( $row['id'], $key, true ) as $test ) {
					$status = isset( $test['status'] ) ? (string) $test['status'] : '';
					$name   = isset( $test['method'] ) ? trim( preg_replace( '/\s+/', ' ', (string) $test['method'] ) ) : '';
					if ( 'completed' !== $status || '' === $name ) {
						continue;
					}
					$methods[ mb_strtolower( $name ) ] = $name;
				}
			}
			if ( ! $methods ) {
				continue;
			}
			if ( ! isset( $stats[ $row['broker_id'] ] ) ) {
				$stats[ $row['broker_id'] ] = array( 'methods' => array(), 'markets' => array() );
			}
			$stats[ $row['broker_id'] ]['methods'] += $methods;
			if ( $row['market'] ) {
				$stats[ $row['broker_id'] ]['markets'][ strtoupper( $row['market'] ) ] = true;
			}
		}
		foreach ( $stats as $id => $stat ) {
			$stats[ $id ] = array(
				'methods' => array_values( $stat['methods'] ),
				'markets' => array_keys( $stat['markets'] ),
			);
		}
		self::$test_stats = $stats;
		return $stats;
	}

	/**
	 * Number of distinct deposit/withdrawal methods tested for a broker.
	 *
	 * @param array $broker Normalized broker.
	 * @return int
	 */
	public static function methods_tested( array $broker ) {
		$stats = self::test_stats();
		return isset( $stats[ $broker['id'] ] ) ? count( $stats[ $broker['id'] ]['methods'] ) : 0;
	}

	/**
	 * Whether completed tests are published for a broker (optionally in one country).
	 *
	 * @param int         $broker_id Broker ID.
	 * @param string|null $code      ISO code, or null for any country.
	 * @return bool
	 */
	public static function has_published_tests( $broker_id, $code = null ) {
		$stats = self::test_stats();
		if ( ! isset( $stats[ $broker_id ] ) ) {
			return false;
		}
		return null === $code || in_array( strtoupper( (string) $code ), $stats[ $broker_id ]['markets'], true );
	}

	/**
	 * Countries offered by the Broker Reviews country filter (A to Z).
	 *
	 * A country qualifies when published research backs it: published test
	 * evidence with completed tests for a published broker, or a published
	 * broker's Accepting residents list containing that country with its
	 * source classified as Tested or Verified at source. The list is one merged set: it carries no counts and no
	 * marker of why a country qualifies, so it cannot be used to tell
	 * test-account countries from other researched countries.
	 *
	 * @return array[] Markets.
	 */
	public static function directory_markets() {
		$codes = array();
		foreach ( self::test_stats() as $stat ) {
			foreach ( $stat['markets'] as $code ) {
				$codes[ $code ] = true;
			}
		}
		foreach ( self::brokers() as $broker ) {
			foreach ( $broker['accepting']['countries'] as $code ) {
				$source = self::availability_source( $broker, $code );
				if ( in_array( $source['source'], array( 'tested', 'verified' ), true ) ) {
					$codes[ strtoupper( $code ) ] = true;
				}
			}
		}
		return array_values(
			array_filter(
				self::markets(),
				static function ( $market ) use ( $codes ) {
					return isset( $codes[ $market['code'] ] );
				}
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Country eligibility (review pages)
	 * ------------------------------------------------------------------ */

	/**
	 * Eligibility of residents of a country for a broker. Only a confirmed
	 * "Not accepting residents" counts as restricted; pending, unknown or
	 * unverified rows stay "unknown" and never hide content.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return string accepting|restricted|unknown
	 */
	public static function eligibility( array $broker, $code ) {
		$availability = self::availability( $broker, $code );
		if ( 'restricted' === $availability ) {
			return 'restricted';
		}
		return 'yes' === $availability ? 'accepting' : 'unknown';
	}

	/**
	 * Country context of a review: heading, description and the message for
	 * the visitor's country. Texts are plain; placeholders are filled by
	 * fxt_core_fill(). Priority for the message: the broker's country row,
	 * then the country's own message, then the defaults in Settings.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array { status, heading, description, change, message, source }
	 */
	public static function country_context( array $broker, $code ) {
		$texts  = self::setting( 'country_context' );
		$status = self::eligibility( $broker, $code );
		$entity = self::entity( $broker, $code );
		$key    = 'restricted' === $status ? 'restricted' : ( 'accepting' === $status ? ( '' !== (string) $entity['name'] ? 'accepting' : 'accepting_no_entity' ) : 'unknown' );

		$message = $texts[ $key ];
		$from    = 'default';
		$market  = self::market( $code );
		$term    = array( 'accepting' => 'fxt_ctx_accepting', 'accepting_no_entity' => 'fxt_ctx_accepting', 'restricted' => 'fxt_ctx_restricted', 'unknown' => 'fxt_ctx_unknown' );
		$country = $market ? trim( (string) get_term_meta( $market['term_id'], $term[ $key ], true ) ) : '';
		if ( '' !== $country ) {
			$message = $country;
			$from    = 'country';
		}
		$row = self::market_row( $broker, $code );
		if ( $row && ! empty( $row['context'] ) && '' !== trim( (string) $row['context'] ) ) {
			$message = (string) $row['context'];
			$from    = 'broker';
		}
		return array(
			'status'      => $status,
			'heading'     => $texts['heading'],
			'description' => $texts['description'],
			'change'      => $texts['change'],
			'message'     => $message,
			'source'      => $from,
		);
	}

	/**
	 * Trader verdict override for the visitor's country.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return string Plain text, or '' to keep the review's own verdict.
	 */
	public static function country_verdict( array $broker, $code ) {
		$row = self::market_row( $broker, $code );
		if ( $row && ! empty( $row['verdict'] ) && '' !== trim( (string) $row['verdict'] ) ) {
			return (string) $row['verdict'];
		}
		if ( 'restricted' === self::eligibility( $broker, $code ) ) {
			$texts = self::setting( 'country_context' );
			return (string) $texts['restricted_verdict'];
		}
		return '';
	}

	/**
	 * Availability of a broker in a market.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return string yes|restricted|pending
	 */
	public static function availability( array $broker, $code ) {
		// From "Accepting residents" only. Before any country is ticked the
		// broker's eligibility is unknown everywhere ("pending").
		$accepting = $broker['accepting'];
		if ( empty( $accepting['configured'] ) ) {
			return 'pending';
		}
		return in_array( strtoupper( (string) $code ), $accepting['countries'], true ) ? 'yes' : 'restricted';
	}

	/**
	 * The legal entity that serves a market: the entity of its Tested residents
	 * row (Entity key), or the first entity when that row names none. Empty for
	 * countries outside Tested residents.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array Entity fields; empty strings when unknown.
	 */
	public static function entity( array $broker, $code ) {
		$found = null;
		// Country-specific entity data exists only for Tested residents.
		if ( ! empty( $broker['entities'] ) && self::is_tested_resident( $broker, $code ) ) {
			$key   = ! empty( $broker['markets'][ $code ]['entity_key'] ) ? $broker['markets'][ $code ]['entity_key'] : '';
			$found = $broker['entities'][0];
			foreach ( $broker['entities'] as $entity ) {
				if ( $key && isset( $entity['key'] ) && $entity['key'] === $key ) {
					$found = $entity;
					break;
				}
			}
		}
		$entity = self::normalize_entity( $found ? $found : array() );
		// Kept for views that only need verified / not verified.
		$entity['verified'] = 'verified' === $entity['source'];
		return $entity;
	}

	/**
	 * Whether the regulatory chain of a country may be shown: the country is
	 * a Tested resident, its legal entity is named and the entity data is
	 * "Verified at source" (or "Tested" backed by published evidence).
	 * Broker-stated or unclassified entity data is never shown there.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return bool
	 */
	public static function entity_is_verified( array $broker, $code ) {
		$code = strtoupper( (string) $code );
		if ( '' === $code || ! self::is_tested_resident( $broker, $code ) ) {
			return false;
		}
		$entity = self::entity( $broker, $code );
		if ( '' === trim( (string) $entity['name'] ) ) {
			return false;
		}
		return in_array( self::entity_source( $broker, $code )['source'], array( 'verified', 'tested' ), true );
	}

	/**
	 * Whether a stored value is a placeholder rather than data
	 * ("TBD", "N/A", "Research pending"...).
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	public static function is_placeholder( $value ) {
		$value = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );
		return '' === $value || (bool) preg_match( '/^(tbd|tbc|n\/?a|-+|\x{2014}|unknown|pending|research pending|to be confirmed|to be determined)\.?$/u', $value );
	}

	/**
	 * Market row (local payments + test status) for a broker.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array|null
	 */
	public static function market_row( array $broker, $code ) {
		return isset( $broker['markets'][ $code ] ) ? $broker['markets'][ $code ] : null;
	}

	/**
	 * Whether any test has started for a broker in a market.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return bool
	 */
	public static function has_tests( array $broker, $code ) {
		$row = self::market_row( $broker, $code );
		if ( ! $row ) {
			return false;
		}
		foreach ( array( 'deposit', 'withdrawal', 'platform', 'support' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Markets where testing has started.
	 *
	 * @param array $broker Normalized broker.
	 * @return string[] ISO codes.
	 */
	public static function tested_markets( array $broker ) {
		$codes = array();
		foreach ( self::markets() as $market ) {
			if ( self::has_tests( $broker, $market['code'] ) ) {
				$codes[] = $market['code'];
			}
		}
		return $codes;
	}

	/**
	 * Brokers available in a market, highest score first.
	 *
	 * @param string $code ISO code.
	 * @return array[]
	 */
	public static function available_in( $code ) {
		return array_values(
			array_filter(
				self::brokers(),
				static function ( $broker ) use ( $code ) {
					return 'yes' === self::availability( $broker, $code );
				}
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Evidence
	 * ------------------------------------------------------------------ */

	/**
	 * All published evidence sets (lightweight).
	 *
	 * @return array[] Each: id, url, title, broker_id, market, status.
	 */
	public static function evidence_index() {
		$posts = get_posts(
			array(
				'post_type'      => Content_Types::EVIDENCE,
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $posts as $post ) {
			$out[] = array(
				'id'        => $post->ID,
				'url'       => get_permalink( $post ),
				'title'     => get_the_title( $post ),
				'broker_id' => (int) get_post_meta( $post->ID, 'fxt_broker_id', true ),
				'market'    => (string) get_post_meta( $post->ID, 'fxt_market', true ),
				'status'    => (string) get_post_meta( $post->ID, 'fxt_status', true ),
				'date'      => $post->post_date,
				'modified'  => get_post_modified_time( 'Y-m-d', false, $post ),
				'author_id' => (int) $post->post_author,
			);
		}
		return $out;
	}

	/**
	 * Published evidence for a broker in a market.
	 *
	 * @param int    $broker_id Broker post ID.
	 * @param string $code      ISO code.
	 * @return array|null Index row.
	 */
	public static function evidence_for( $broker_id, $code ) {
		foreach ( self::evidence_index() as $row ) {
			if ( (int) $broker_id === $row['broker_id'] && $code === $row['market'] ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Full evidence record.
	 *
	 * @param int|\WP_Post $post Evidence post.
	 * @return array|null
	 */
	public static function evidence( $post ) {
		$post = get_post( $post );
		if ( ! $post || Content_Types::EVIDENCE !== $post->post_type ) {
			return null;
		}
		$get = static function ( $key, $default = array() ) use ( $post ) {
			$value = get_post_meta( $post->ID, $key, true );
			return ( '' === $value || null === $value ) ? $default : $value;
		};
		$deposits    = array_values( (array) $get( 'fxt_deposits' ) );
		$withdrawals = array_values( (array) $get( 'fxt_withdrawals' ) );

		// Records shown in the evidence viewer come from the test rows themselves.
		// Version 1.0 kept them in a separate "fxt_records" list; it is still read.
		$legacy = array();
		foreach ( (array) $get( 'fxt_records' ) as $record ) {
			if ( ! empty( $record['test_id'] ) ) {
				$legacy[ $record['test_id'] ] = $record;
			}
		}
		$records = array();
		$detail  = array( 'steps', 'broker_status', 'bank_status', 'account', 'holder', 'bank', 'reference', 'note', 'screenshot' );
		foreach ( array_merge( $deposits, $withdrawals ) as $row ) {
			if ( empty( $row['id'] ) ) {
				continue;
			}
			$record = isset( $legacy[ $row['id'] ] ) ? array_merge( $legacy[ $row['id'] ], array_filter( (array) $row ) ) : (array) $row;
			if ( ! array_intersect_key( array_filter( $record ), array_flip( $detail ) ) ) {
				continue; // Nothing to show beyond the table row.
			}
			$record['steps']         = self::parse_steps( isset( $record['steps'] ) ? (string) $record['steps'] : '' );
			$records[ $row['id'] ] = $record;
		}
		return array(
			'id'          => $post->ID,
			'url'         => get_permalink( $post ),
			'broker_id'   => (int) $get( 'fxt_broker_id', 0 ),
			'market'      => (string) $get( 'fxt_market', '' ),
			'status'      => (string) $get( 'fxt_status', 'in-progress' ),
			'period'      => wp_parse_args( $get( 'fxt_period' ), array( 'from' => '', 'to' => '' ) ),
			'account'     => (string) $get( 'fxt_account_label', '' ),
			'summary'     => (array) $get( 'fxt_summary' ),
			'timeline'    => (array) $get( 'fxt_timeline' ),
			'deposits'    => $deposits,
			'withdrawals' => $withdrawals,
			'records'     => $records,
			'author_id'   => (int) $post->post_author,
			'modified'    => get_post_modified_time( 'Y-m-d', false, $post ),
		);
	}

	/**
	 * Parse "YYYY-MM-DD HH:MM | label" lines.
	 *
	 * @param string $text Raw steps.
	 * @return array[] Each: at, label.
	 */
	private static function parse_steps( $text ) {
		$steps = array();
		foreach ( preg_split( '/[\r\n]+/', $text ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 === count( $parts ) && '' !== $parts[1] ) {
				$steps[] = array( 'at' => str_replace( ' ', 'T', $parts[0] ), 'label' => $parts[1] );
			}
		}
		return $steps;
	}

	/* ---------------------------------------------------------------------
	 * Authors
	 * ------------------------------------------------------------------ */

	/**
	 * Author profile from a user ID.
	 *
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	public static function author( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return null;
		}
		$get = static function ( $key, $default = '' ) use ( $user ) {
			$value = get_user_meta( $user->ID, $key, true );
			return ( '' === $value || null === $value ) ? $default : $value;
		};
		$name   = $user->display_name;
		$avatar = (int) $get( 'fxt_avatar_id', 0 );
		$bio    = (string) get_user_meta( $user->ID, 'description', true );

		return array(
			'id'         => $user->ID,
			'name'       => $name,
			'url'        => get_author_posts_url( $user->ID ),
			'initials'   => strtoupper( mb_substr( $name, 0, 1 ) ),
			'avatar_id'  => $avatar,
			'role'       => (string) $get( 'fxt_role_title' ),
			'short_bio'  => (string) $get( 'fxt_short_bio', wp_trim_words( $bio, 40 ) ),
			'bio'        => $bio,
			'location'   => (string) $get( 'fxt_location' ),
			'since'      => (string) $get( 'fxt_since' ),
			'languages'  => (array) $get( 'fxt_languages', array() ),
			'markets'    => (array) $get( 'fxt_markets', array() ),
			'expertise'  => (array) $get( 'fxt_expertise', array() ),
			'stats'      => (array) $get( 'fxt_stats', array() ),
			'principles' => (array) $get( 'fxt_principles', array() ),
			'disclosure' => (string) $get( 'fxt_disclosure' ),
			'social'     => array_values(
				array_filter(
					(array) $get( 'fxt_social', array() ),
					static function ( $link ) {
						return ! empty( $link['url'] );
					}
				)
			),
			'simulated'  => (bool) $get( 'fxt_simulated', false ),
		);
	}
}
