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

	const CACHE_KEY     = 'fxt_core_brokers_v1';
	const COOKIE        = 'fxt_country';
	const SETTINGS      = 'fxt_settings';

	/** @var array|null Per-request market cache. */
	private static $markets = null;

	/** @var array|null Per-request broker cache. */
	private static $brokers = null;

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
		self::$brokers = null;
		self::$markets = null;
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
		foreach ( array( 'weights', 'pages' ) as $group ) {
			$settings[ $group ] = array_merge( $defaults[ $group ], is_array( $settings[ $group ] ) ? array_filter( $settings[ $group ], 'is_numeric' ) : array() );
		}
		return $settings;
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

	/**
	 * All markets, ordered.
	 *
	 * @return array[] Each: code, name, currency, status, order, term_id.
	 */
	public static function markets() {
		if ( null !== self::$markets ) {
			return self::$markets;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => Content_Types::MARKET,
				'hide_empty' => false,
			)
		);
		$markets = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$code = strtoupper( (string) get_term_meta( $term->term_id, 'fxt_iso', true ) );
				$code = $code ? $code : strtoupper( $term->slug );
				$markets[] = array(
					'code'     => $code,
					'name'     => $term->name,
					'currency' => (string) get_term_meta( $term->term_id, 'fxt_currency', true ),
					'status'   => (string) get_term_meta( $term->term_id, 'fxt_status', true ),
					'order'    => (int) get_term_meta( $term->term_id, 'fxt_order', true ),
					'term_id'  => $term->term_id,
				);
			}
		}
		usort(
			$markets,
			static function ( $a, $b ) {
				return $a['order'] === $b['order'] ? strcmp( $a['name'], $b['name'] ) : $a['order'] - $b['order'];
			}
		);
		self::$markets = $markets;
		return $markets;
	}

	/**
	 * One market by ISO code.
	 *
	 * @param string $code ISO code.
	 * @return array|null
	 */
	public static function market( $code ) {
		foreach ( self::markets() as $market ) {
			if ( $market['code'] === strtoupper( (string) $code ) ) {
				return $market;
			}
		}
		return null;
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

		$name  = get_the_title( $post );
		$score = $meta( 'fxt_score' );

		return array(
			'id'              => $post->ID,
			'slug'            => $post->post_name,
			'name'            => $name,
			'url'             => get_permalink( $post ),
			'excerpt'         => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
			'logo_id'         => (int) get_post_thumbnail_id( $post ),
			'monogram'        => $meta( 'fxt_monogram', strtoupper( mb_substr( $name, 0, 2 ) ) ),
			'author_id'       => (int) $post->post_author,
			'status'          => $meta( 'fxt_status', 'published' ),
			'score'           => null === $score ? null : (float) $score,
			'breakdown'       => $meta( 'fxt_score_breakdown', array() ),
			'reviewed'        => $meta( 'fxt_reviewed', '' ),
			'founded'         => $meta( 'fxt_founded', '' ),
			'min_deposit'     => null === $meta( 'fxt_min_deposit' ) ? null : (float) $meta( 'fxt_min_deposit' ),
			'currencies'      => $meta( 'fxt_currencies', array() ),
			'other_platforms' => $meta( 'fxt_other_platforms', '' ),
			'trading'         => wp_parse_args( $meta( 'fxt_trading', array() ), array( 'leverage' => '', 'execution' => '', 'order_types' => '' ) ),
			'costs'           => wp_parse_args( $meta( 'fxt_costs', array() ), array( 'spread' => '', 'commission' => '', 'swap' => '', 'other_fees' => '' ) ),
			'payments'        => wp_parse_args( $meta( 'fxt_payments', array() ), array( 'bank' => false, 'cards' => false, 'ewallets' => false, 'crypto' => false, 'time_local' => '', 'time_cards' => '', 'time_ewallets' => '', 'time_crypto' => '' ) ),
			'accounts'        => $meta( 'fxt_accounts', array() ),
			'entities'        => $meta( 'fxt_entities', array() ),
			'markets'         => $meta( 'fxt_markets', array() ),
			'affiliate_url'   => $meta( 'fxt_affiliate_url', '' ),
			'regulators'      => array_values( $terms( Content_Types::REGULATOR ) ),
			'regulator_slugs' => array_keys( $terms( Content_Types::REGULATOR ) ),
			'platforms'       => $terms( Content_Types::PLATFORM ),
			'account_types'   => array_keys( $terms( Content_Types::ACCOUNT_TYPE ) ),
		);
	}

	/**
	 * Availability of a broker in a market.
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return string yes|restricted|pending
	 */
	public static function availability( array $broker, $code ) {
		return ! empty( $broker['markets'][ $code ]['availability'] ) ? $broker['markets'][ $code ]['availability'] : 'pending';
	}

	/**
	 * The legal entity that serves a market (falls back to the first entity).
	 *
	 * @param array  $broker Normalized broker.
	 * @param string $code   ISO code.
	 * @return array Entity fields; empty strings when unknown.
	 */
	public static function entity( array $broker, $code ) {
		$empty = array( 'key' => '', 'name' => '', 'regulator' => '', 'licence' => '', 'jurisdiction' => '', 'protection' => '', 'verified' => false );
		if ( empty( $broker['entities'] ) ) {
			return $empty;
		}
		$key = ! empty( $broker['markets'][ $code ]['entity_key'] ) ? $broker['markets'][ $code ]['entity_key'] : '';
		foreach ( $broker['entities'] as $entity ) {
			if ( $key && isset( $entity['key'] ) && $entity['key'] === $key ) {
				return wp_parse_args( array_filter( $entity, static function ( $v ) { return null !== $v; } ), $empty );
			}
		}
		return wp_parse_args( array_filter( $broker['entities'][0], static function ( $v ) { return null !== $v; } ), $empty );
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
		$records = array();
		foreach ( (array) $get( 'fxt_records' ) as $record ) {
			if ( ! empty( $record['test_id'] ) ) {
				$record['steps']                = self::parse_steps( isset( $record['steps'] ) ? (string) $record['steps'] : '' );
				$records[ $record['test_id'] ] = $record;
			}
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
			'deposits'    => (array) $get( 'fxt_deposits' ),
			'withdrawals' => (array) $get( 'fxt_withdrawals' ),
			'records'     => $records,
			'author_id'   => (int) $post->post_author,
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
			'simulated'  => (bool) $get( 'fxt_simulated', false ),
		);
	}
}
